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
                $price = $cat->planPrice; // هنضيف relation كمان
                return [
                    'category_id'      => $cat->id,
                    'category_name'    => $cat->name,
                    'category_slug'    => $cat->slug,
                    'price_featured'   => $price?->price_featured ?? 0,
                    'price_standard'   => $price?->price_standard ?? 0,
                ];
            });

        return response()->json($rows);
    }

    // POST /api/admin/category-plan-prices
    public function store(Request $request)
    {
        $data = $request->validate([
            'items'                       => ['required', 'array'],
            'items.*.category_id'        => ['required', 'integer', 'exists:categories,id'],
            'items.*.price_featured'     => ['required', 'integer', 'min:0'],
            'items.*.price_standard'     => ['required', 'integer', 'min:0'],
        ]);

        foreach ($data['items'] as $row) {
            CategoryPlanPrice::updateOrCreate(
                ['category_id' => $row['category_id']],
                [
                    'price_featured' => $row['price_featured'],
                    'price_standard' => $row['price_standard'],
                ]
            );
        }

        return response()->json([
            'message' => 'تم حفظ أسعار الباقات بنجاح.',
        ]);
    }
}
