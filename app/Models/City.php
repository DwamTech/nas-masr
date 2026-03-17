<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;


class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'governorate_id', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'city_id');
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
