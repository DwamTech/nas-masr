<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class UserConversation extends Model
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'users_conversations';

    /**
     * The attributes that are mass assignable.
     */
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'receiver_id',
        'receiver_type',
        'message',
        'attachment',
        'read_at',
        'type',         // conversation type: peer, support
        'content_type', // message content type: text, listing, image...
        'listing_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'listing_id' => 'integer',
    ];

    /**
     * Conversation types constants.
     */
    const TYPE_PEER = 'peer';           // محادثة بين عملاء
    const TYPE_SUPPORT = 'support';     // محادثة مع الدعم
    const TYPE_BROADCAST = 'broadcast'; // رسالة جماعية

    /**
     * Message Content Types.
     */
    const CONTENT_TYPE_TEXT = 'text';
    const CONTENT_TYPE_LISTING = 'listing_inquiry';
    const CONTENT_TYPE_IMAGE = 'image';
    const CONTENT_TYPE_VIDEO = 'video';
    const CONTENT_TYPE_AUDIO = 'audio';
    const CONTENT_TYPE_FILE  = 'file';

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the sender (User or Admin).
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Related Listing (optional).
     */
    public function listing(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Get the receiver (User or Admin or null).
     */
    public function receiver(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope to filter by conversation type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get peer conversations.
     */
    public function scopePeer(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PEER);
    }

    /**
     * Scope to get support conversations.
     */
    public function scopeSupport(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SUPPORT);
    }

    /**
     * Scope to get broadcast messages.
     */
    public function scopeBroadcast(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_BROADCAST);
    }

    /**
     * Scope to get unread messages.
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to filter by conversation ID.
     */
    public function scopeInConversation(Builder $query, string $conversationId): Builder
    {
        return $query->where('conversation_id', $conversationId);
    }

    /**
     * Scope to get messages for a specific user (sent or received).
     */
    public function scopeForUser(Builder $query, int $userId, string $userType = User::class): Builder
    {
        return $query->where(function ($q) use ($userId, $userType) {
            $q->where(function ($sub) use ($userId, $userType) {
                $sub->where('sender_id', $userId)
                    ->where('sender_type', $userType);
            })->orWhere(function ($sub) use ($userId, $userType) {
                $sub->where('receiver_id', $userId)
                    ->where('receiver_type', $userType);
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Mark message as read.
     */
    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            $this->read_at = now();
            return $this->save();
        }
        return true;
    }

    /**
     * Check if message is read.
     */
    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Check if message is unread.
     */
    public function isUnread(): bool
    {
        return $this->read_at === null;
    }
}

