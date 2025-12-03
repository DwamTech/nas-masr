<?php

namespace App\Http\Controllers;

use App\Models\CategoryPlanPrice;
use App\Support\Section;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    public function show(string $section)
    {
        $sec = Section::fromSlug($section);

        $prices = CategoryPlanPrice::where('category_id', $sec->id())->first();

        return response()->json([
            'category' => [
                'id'   => $sec->id(),
                'slug' => $sec->slug,
                'name' => $sec->name,
            ],
            'price_featured' => $prices?->price_featured ?? 0,
            'price_standard' => $prices?->price_standard ?? 0,
        ]);
    }
}
