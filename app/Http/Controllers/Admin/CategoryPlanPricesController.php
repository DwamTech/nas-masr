<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryPlanPrice;
use Illuminate\Http\Request;

class CategoryPlanPricesController extends Controller
{
    public function index()
    {
        $rows = Category::orderBy('name')
            ->get()
            ->map(function (Category $cat) {
                $price = $cat->planPrice; // relation hasOne

                return [
                    'category_id'        => $cat->id,
                    'category_name'      => $cat->name,
                    'category_slug'      => $cat->slug,

                    // المميزة
                    'price_featured'     => (float) ($price->price_featured ?? 0),
                    'featured_ad_price'  => (float) ($price->featured_ad_price ?? 0),
                    'featured_days'      => (int)   ($price->featured_days ?? 0),
                    'featured_ads_count' => (int)   ($price->featured_ads_count ?? 0),

                    // ستاندرد
                    'price_standard'     => (float) ($price->price_standard ?? 0),
                    'standard_ad_price'  => (float) ($price->standard_ad_price ?? 0),
                    'standard_days'      => (int)   ($price->standard_days ?? 0),
                    'standard_ads_count' => (int)   ($price->standard_ads_count ?? 0),
                ];
            });

        return response()->json($rows);
    }


    // POST /api/admin/category-plan-prices
    public function store(Request $request)
    {
        $data = $request->validate([
            'items'                            => ['required', 'array'],

            'items.*.category_id'             => ['required', 'integer', 'exists:categories,id'],

            'items.*.price_featured'          => ['required', 'numeric', 'min:0'],
            'items.*.featured_ad_price'       => ['required', 'numeric', 'min:0'],
            'items.*.featured_days'           => ['required', 'integer', 'min:0'],
            'items.*.featured_ads_count'      => ['required', 'integer', 'min:0'],

            'items.*.price_standard'          => ['required', 'numeric', 'min:0'],
            'items.*.standard_ad_price'       => ['required', 'numeric', 'min:0'],
            'items.*.standard_days'           => ['required', 'integer', 'min:0'],
            'items.*.standard_ads_count'      => ['required', 'integer', 'min:0'],
            'items.*.free_ad_max_price'       => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($data['items'] as $row) {
            CategoryPlanPrice::updateOrCreate(
                ['category_id' => $row['category_id']],
                [
                    'price_featured'    => $row['price_featured']    ?? 0,
                    'featured_ad_price' => $row['featured_ad_price'] ?? 0,
                    'featured_days'     => $row['featured_days']     ?? 0,
                    'featured_ads_count'=> $row['featured_ads_count']?? 0,

                    'price_standard'    => $row['price_standard']    ?? 0,
                    'standard_ad_price' => $row['standard_ad_price'] ?? 0,
                    'standard_days'     => $row['standard_days']     ?? 0,
                    'standard_ads_count'=> $row['standard_ads_count']?? 0,
                    
                    'free_ad_max_price' => $row['free_ad_max_price'] ?? 0,
                ]
            );
        }

        return response()->json([
            'message' => 'تم حفظ أسعار الباقات بنجاح.',
        ]);
    }
}
