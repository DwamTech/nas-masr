<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\categoryRequest;
use App\Http\Resources\Admin\CategoryResource;
use App\Models\Category;
use App\Services\OptionRankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class categoryController extends Controller
{
    public function index(Request $request)
    {

        $user = Request()->user();
        if ($user?->role == 'admin') {
            $q = Category::query()
                // ->where('is_active', true) 
                ->orderBy('sort_order', 'asc');
        } else {
            $q = Category::query()
                ->where('is_active', true)
                ->orderBy('sort_order', 'asc');

            if ($request->filled('active')) {
                $q->where('is_active', (bool) $request->boolean('active'));
            }
        }

        return CategoryResource::collection($q->get());
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    // POST /api/admin/categories
    public function store(categoryRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('default_image')) {
            $path = $request->file('default_image')->store('categories', 'uploads');
            $data['default_image'] = basename($path);
        }

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('categories', 'uploads');
            $data['icon'] = basename($path);
        }

        $cat = Category::create($data);

        return response()->json([
            'message' => 'تم إنشاء القسم بنجاح',
            'data' => $cat,
        ], 201);
    }

    // PUT /api/admin/categories/{category}
    public function update(CategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        if ($request->boolean('remove_default_image')) {
            $data['default_image'] = null;
        }

        if ($request->hasFile('default_image')) {
            $path = $request->file('default_image')->store('categories', 'uploads');
            $data['default_image'] = basename($path);
        }

        if ($request->hasFile('icon')) {
            $path = $request->file('icon')->store('categories', 'uploads');
            $data['icon'] = basename($path);
        }

        $category->update($data);

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

    public function usageReport()
    {
        $categories = Category::withCount('listings')->get();

        return response()->json([
            'data' => $categories->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'icon_url' => $cat->icon_url,
                    'listings_count' => $cat->listings_count,
                ];
            }),
        ]);
    }

    /**
     * Update option ranks for a category field.
     * 
     * POST /api/admin/categories/{slug}/options/ranks
     * 
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOptionRanks(Request $request, string $slug)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'field' => 'required|string|max:255',
                'ranks' => 'required|array|min:1',
                'ranks.*.option' => 'required|string',
                'ranks.*.rank' => 'required|integer|min:1',
            ]);

            // Check if category exists
            $category = Category::where('slug', $slug)->first();
            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'القسم غير موجود',
                ], 404);
            }

            // Use service to update ranks
            $service = new OptionRankService();
            $service->updateRanks($slug, $validated['field'], $validated['ranks']);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الترتيب بنجاح',
                'data' => [
                    'updated_count' => count($validated['ranks']),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-throw validation exceptions to let Laravel handle them
            throw $e;

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update option ranks', [
                'category' => $slug,
                'field' => $validated['field'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حفظ الترتيب',
            ], 500);
        }
    }
}
