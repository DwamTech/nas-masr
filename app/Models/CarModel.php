<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class CarModel extends Model
{
    //
    use HasFactory;
protected $table='models';
    protected $fillable = ['name', 'make_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function make()
    {
        return $this->belongsTo(Make::class);
    }

    public function cars()
    {
        return $this->hasMany(Car::class);
    }
}
