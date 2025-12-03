<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPlanSubscription extends Model
{
    protected $table = 'user_plan_subscriptions';

    protected $fillable = [
        'user_id',
        'category_id',
        'plan_type',
        'days',
        'subscribed_at',
        'expires_at',
        'price',
        'ad_price',
        'payment_status',
        'payment_reference',
    ];

    protected $casts = [
        'days' => 'integer',
        'subscribed_at' => 'datetime',
        'expires_at' => 'datetime',
        'price' => 'decimal:2',
        'ad_price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}

