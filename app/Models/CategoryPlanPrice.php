<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryPlanPrice extends Model
{
    protected $table = 'category_plan_prices';

    protected $fillable = [
        'category_id',
        'price_featured',
        'price_standard',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
