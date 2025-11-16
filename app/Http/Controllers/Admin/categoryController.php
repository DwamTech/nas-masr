<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\categoryRequest;
use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class categoryController extends Controller
{
    public function index(Request $request)
    {
        $q = Category::query()
            ->where('is_active', true) 
            ->orderBy('sort_order', 'asc');

        if ($request->filled('active')) {
            $q->where('is_active', (bool) $request->boolean('active'));
        }

        return CategoryResource::collection($q->get());
    }

    // POST /api/admin/categories
    public function store(categoryRequest $request)
    {
        $cat = Category::create($request->validated());

        return response()->json([
            'message' => 'تم إنشاء القسم بنجاح',
            'data' => $cat,
        ], 201);
    }

    // PUT /api/admin/categories/{category}
    public function update(CategoryRequest $request, Category $category)
    {
        $category->update($request->validated());

        return response()->json([
            'message' => 'تم تحديث القسم بنجاح',
            'data' => $category,
        ]);
    }


    public function destroy(Category $category)
    {
        $category->update(['is_active' => false]);

        return response()->json([
            'message' => 'تم تعطيل القسم',
        ]);
    }
}
