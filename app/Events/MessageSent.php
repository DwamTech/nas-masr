<?php

namespace App\Events;

use App\Models\UserConversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message that was sent.
     */
    public UserConversation $message;

    /**
     * Create a new event instance.
     */
    public function __construct(UserConversation $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Send to receiver's private channel (if exists)
        if ($this->message->receiver_id) {
            $channels[] = new PrivateChannel('user.' . $this->message->receiver_id);
        }

        // For support messages, also broadcast to admin support channel
        if ($this->message->type === 'support') {
            $channels[] = new PrivateChannel('admin.support');
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender' => [
                'id' => $this->message->sender_id,
                'name' => $this->message->sender?->name ?? 'Unknown',
            ],
            'message' => $this->message->message,
            'type' => $this->message->type,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}

