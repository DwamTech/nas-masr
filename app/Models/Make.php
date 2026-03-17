<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Make extends Model
{
    //
    use HasFactory;

    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function models()
    {
        return $this->hasMany(CarModel::class);
    }

    public function activeModels()
    {
        return $this->hasMany(CarModel::class)->where('is_active', true);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
