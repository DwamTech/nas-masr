<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Governorate extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function activeCities()
    {
        return $this->hasMany(City::class)->where('is_active', true);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
    public function listings()
    {
        return $this->hasMany(Listing::class, 'governorate_id');
    }
}
