<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'slug' => $this->faker->unique()->slug,
            'title' => $this->faker->sentence(3),
            'parent_id' => null,
            'icon' => null,
            'default_image' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
