<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryFieldOptionRank extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'category_field_option_ranks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'field_name',
        'option_value',
        'rank',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rank' => 'integer',
    ];

    /**
     * Get the category that owns the option rank.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Scope a query to get ranks for a specific field.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $categoryId
     * @param  string  $fieldName
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForField($query, int $categoryId, string $fieldName)
    {
        return $query->where('category_id', $categoryId)
                     ->where('field_name', $fieldName)
                     ->orderBy('rank');
    }
}
