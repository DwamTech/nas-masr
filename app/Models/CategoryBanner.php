<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryBanner extends Model
{
    const TYPE_HOME_PAGE = 'home_page';
    const TYPE_AD_CREATION = 'ad_creation';

    protected $fillable = [
        'category_id',
        'banner_type',
        'banner_image',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    protected $appends = ['banner_url'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getBannerUrlAttribute(): ?string
    {
        if (!$this->banner_image) {
            return null;
        }

        return asset('storage/uploads/banners/' . $this->banner_image);
    }
}
