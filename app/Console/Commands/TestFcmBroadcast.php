<?php

namespace App\Console\Commands;

use App\Services\FirebaseService;
use App\Models\User;
use Illuminate\Console\Command;

class TestFcmBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fcm:test-broadcast {message?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test FCM notification to all users with FCM tokens';

    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        parent::__construct();
        $this->firebase = $firebase;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customMessage = $this->argument('message');
        
        $this->info('🔥 Starting FCM Broadcast Test...');
        $this->newLine();

        // Get all users with FCM tokens
        $users = User::whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->get();

        if ($users->isEmpty()) {
            $this->error('❌ No users found with FCM tokens!');
            $this->info('💡 Users need to login from the app first to register their FCM tokens.');
            return 1;
        }

        $this->info("📱 Found {$users->count()} users with FCM tokens");
        $this->newLine();

        $title = 'إشعار تجريبي من ناس مصر';
        $body = $customMessage ?? '✅ نظام الإشعارات يعمل بنجاح! هذا إشعار تجريبي للتأكد من عمل الخدمة.';

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $success = 0;
        $failed = 0;
        $failedUsers = [];

        foreach ($users as $user) {
            $result = $this->firebase->sendToUser(
                $user->fcm_token,
                $title,
                $body,
                [
                    'type' => 'test_broadcast',
                    'test' => true,
                    'timestamp' => now()->toIso8601String(),
                ]
            );

            if ($result) {
                $success++;
            } else {
                $failed++;
                $failedUsers[] = [
                    'id' => $user->id,
                    'name' => $user->name ?? 'N/A',
                ];
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Results
        $this->info('📊 Results:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Users', $users->count()],
                ['✅ Success', $success],
                ['❌ Failed', $failed],
            ]
        );

        if ($failed > 0) {
            $this->newLine();
            $this->warn('⚠️  Failed Users:');
            $this->table(
                ['User ID', 'Name'],
                $failedUsers
            );
        }

        $this->newLine();
        if ($success > 0) {
            $this->info('🎉 Broadcast completed successfully!');
            $this->info('💡 Check the app to see if notifications arrived.');
        } else {
            $this->error('❌ All notifications failed. Check Firebase credentials and logs.');
        }

        return 0;
    }
}
