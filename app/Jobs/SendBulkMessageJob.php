<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\UserConversation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SendBulkMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param array $userIds Array of user IDs to send message to
     * @param string $message The message content
     * @param int $adminId The admin sending the message
     * @param string|null $title Optional title for the broadcast
     */
    public function __construct(
        private array $userIds,
        private string $message,
        private int $adminId,
        private ?string $title = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $admin = User::find($this->adminId);
        
        if (!$admin) {
            Log::error('SendBulkMessageJob: Admin not found', [
                'admin_id' => $this->adminId,
            ]);
            return;
        }

        $conversationId = (string) Str::uuid();
        $batchSize = 100;
        $totalSent = 0;
        $failed = 0;

        Log::info('SendBulkMessageJob: Starting broadcast', [
            'admin_id' => $this->adminId,
            'total_recipients' => count($this->userIds),
            'conversation_id' => $conversationId,
        ]);

        // Process in batches to avoid memory issues
        $chunks = array_chunk($this->userIds, $batchSize);

        foreach ($chunks as $chunkIndex => $chunk) {
            $insertData = [];
            $now = now();

            foreach ($chunk as $userId) {
                // Skip if user doesn't exist
                if (!User::where('id', $userId)->exists()) {
                    $failed++;
                    continue;
                }

                $insertData[] = [
                    'conversation_id' => $conversationId,
                    'sender_id' => $this->adminId,
                    'sender_type' => User::class,
                    'receiver_id' => $userId,
                    'receiver_type' => User::class,
                    'message' => $this->message,
                    'type' => UserConversation::TYPE_BROADCAST,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($insertData)) {
                try {
                    UserConversation::insert($insertData);
                    $totalSent += count($insertData);
                } catch (\Exception $e) {
                    Log::error('SendBulkMessageJob: Batch insert failed', [
                        'chunk_index' => $chunkIndex,
                        'error' => $e->getMessage(),
                    ]);
                    $failed += count($insertData);
                }
            }

            // Log progress
            Log::info('SendBulkMessageJob: Progress', [
                'chunk' => $chunkIndex + 1,
                'total_chunks' => count($chunks),
                'sent_so_far' => $totalSent,
            ]);
        }

        Log::info('SendBulkMessageJob: Completed', [
            'admin_id' => $this->adminId,
            'conversation_id' => $conversationId,
            'total_sent' => $totalSent,
            'failed' => $failed,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkMessageJob: Job failed', [
            'admin_id' => $this->adminId,
            'recipients_count' => count($this->userIds),
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'broadcast',
            'admin:' . $this->adminId,
            'recipients:' . count($this->userIds),
        ];
    }
}
