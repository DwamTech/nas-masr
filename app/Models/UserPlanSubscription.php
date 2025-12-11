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
        'ads_total',
        'ads_used',
        'payment_status',
        'payment_reference',
        'payment_method',
    ];

    protected $casts = [
        'days' => 'integer',
        'subscribed_at' => 'datetime',
        'expires_at' => 'datetime',
        'price' => 'decimal:2',
        'ad_price' => 'decimal:2',
        'ads_total' => 'integer',
        'ads_used' => 'integer',
    ];

    /**
     * الإعلانات المتبقية في الاشتراك
     */
    public function getAdsRemainingAttribute(): int
    {
        return max(0, (int) $this->ads_total - (int) $this->ads_used);
    }

    /**
     * هل الاشتراك نشط؟ (زمنياً ولديه رصيد إعلانات)
     */
    public function getIsActiveAttribute(): bool
    {
        $timeOk = !$this->expires_at || $this->expires_at->isFuture();
        return $timeOk && $this->ads_remaining > 0;
    }

    /**
     * استهلاك إعلان من الاشتراك
     */
    public function consumeAd(int $count = 1): bool
    {
        if ($this->ads_remaining < $count) {
            return false;
        }
        $this->increment('ads_used', $count);
        return true;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
