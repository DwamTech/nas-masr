<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'icon',
        'is_active',
    ];

    protected $appends = ['icon_url'];

    public function getIconUrlAttribute(): ?string
    {
        if (!$this->icon) {
            return null;
        }

        // لو انت مخزنها في storage/app/public/categories
        return asset('storage/categories/' . $this->icon);

        // ولو حاططها في public/categories مباشرة
        // return asset('categories/' . $this->icon);
    }
}
