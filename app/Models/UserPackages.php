<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UserPackages extends Model
{
    protected $table = 'user_packages';

    protected $fillable = [
        'user_id',

        'featured_ads',
        'featured_ads_used',
        'standard_ads',
        'standard_ads_used',

        'featured_days',
        'featured_start_date',
        'featured_expire_date',
        'standard_days',
        'standard_start_date',
        'standard_expire_date',

        'days',
        'start_date',
        'expire_date',

        'categories',
    ];

    protected $casts = [
        'featured_ads' => 'integer',
        'featured_ads_used' => 'integer',
        'standard_ads' => 'integer',
        'standard_ads_used' => 'integer',

        'featured_days' => 'integer',
        'featured_start_date' => 'datetime',
        'featured_expire_date' => 'datetime',

        'standard_days' => 'integer',
        'standard_start_date' => 'datetime',
        'standard_expire_date' => 'datetime',

        'days' => 'integer',
        'start_date' => 'datetime',
        'expire_date' => 'datetime',

        'categories' => 'array',
    ];

    protected $appends = [
        'featured_ads_remaining',
        'standard_ads_remaining',
        'featured_active',
        'standard_active',
        // 'active', 
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expire_date')->orWhere('expire_date', '>=', now());
        });
    }

    public function getFeaturedAdsRemainingAttribute(): int
    {
        return max(0, (int)$this->featured_ads - (int)$this->featured_ads_used);
    }

    public function getStandardAdsRemainingAttribute(): int
    {
        return max(0, (int)$this->standard_ads - (int)$this->standard_ads_used);
    }

    // UserPackages.php

    public function getFeaturedActiveAttribute(): bool
    {
        $timeOk = $this->featured_expire_date
            ? $this->featured_expire_date->isFuture()
            : ((int)$this->featured_days === 0); // بدون مدة = مفيش انتهاء زمني
        return $timeOk && $this->featured_ads_remaining > 0;
    }

    public function getStandardActiveAttribute(): bool
    {
        $timeOk = $this->standard_expire_date
            ? $this->standard_expire_date->isFuture()
            : ((int)$this->standard_days === 0);
        return $timeOk && $this->standard_ads_remaining > 0;
    }


    public function getActiveAttribute(): bool
    {
        return (bool) $this->featured_active || (bool) $this->standard_active;
    }


    public function startPlanNow(string $plan, ?int $days = null): void
    {
        $plan = strtolower($plan);
        if ($plan === 'featured') {
            $d = $days ?? (int)$this->featured_days;
            $this->featured_start_date = now();
            $this->featured_expire_date = $d > 0 ? now()->copy()->addDays($d) : null;
        } elseif ($plan === 'standard') {
            $d = $days ?? (int)$this->standard_days;
            $this->standard_start_date = now();
            $this->standard_expire_date = $d > 0 ? now()->copy()->addDays($d) : null;
        }
        $this->save();
    }

    public function consumeFeatured(): bool
    {
        if (!$this->featured_active) return false;
        if ($this->featured_ads_remaining <= 0) return false;
        $this->increment('featured_ads_used');
        return true;
    }

    public function consumeStandard(): bool
    {
        if (!$this->standard_active) return false;
        if ($this->standard_ads_remaining <= 0) return false;
        $this->increment('standard_ads_used');
        return true;
    }
}
