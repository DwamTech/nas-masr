<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingPayment extends Model
{
    protected $table = 'listing_payments';

    protected $fillable = [
        'listing_id',
        'user_id',
        'category_id',
        'plan_type',
        'amount',
        'currency',
        'paid_at',
        'payment_reference',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
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

