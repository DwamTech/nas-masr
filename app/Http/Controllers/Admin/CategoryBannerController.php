<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryBannerRequest;
use App\Models\CategoryBanner;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryBannerController extends Controller
{
    /**
     * Display all banners (Admin)
     */
    public function index(Request $request)
    {
        $query = CategoryBanner::with('category')
            ->orderBy('display_order', 'asc');

        // Filter by type if provided
        if ($request->filled('banner_type')) {
            $query->where('banner_type', $request->banner_type);
        }

        $banners = $query->get();

        return response()->json([
            'data' => $banners->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'category_id' => $banner->category_id,
                    'category_name' => $banner->category?->name,
                    'category_slug' => $banner->category?->slug,
                    'banner_type' => $banner->banner_type,
                    'banner_url' => $banner->banner_url,
                    'is_active' => $banner->is_active,
                    'display_order' => $banner->display_order,
                    'created_at' => $banner->created_at,
                ];
            }),
        ]);
    }

    /**
     * Display specific banner
     */
    public function show(CategoryBanner $banner)
    {
        $banner->load('category');

        return response()->json([
            'data' => [
                'id' => $banner->id,
                'category_id' => $banner->category_id,
                'category_name' => $banner->category?->name,
                'category_slug' => $banner->category?->slug,
                'banner_type' => $banner->banner_type,
                'banner_url' => $banner->banner_url,
                'is_active' => $banner->is_active,
                'display_order' => $banner->display_order,
            ],
        ]);
    }

    /**
     * Store new banner
     */
    public function store(CategoryBannerRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('banner_image')) {
            $path = $request->file('banner_image')->store('banners', 'uploads');
            $data['banner_image'] = basename($path);
        }

        $banner = CategoryBanner::create($data);

        return response()->json([
            'message' => 'تم إضافة البانر بنجاح',
            'data' => $banner->load('category'),
        ], 201);
    }

    /**
     * Update existing banner
     */
    public function update(CategoryBannerRequest $request, CategoryBanner $banner)
    {
        $data = $request->validated();

        if ($request->hasFile('banner_image')) {
            $path = $request->file('banner_image')->store('banners', 'uploads');
            $data['banner_image'] = basename($path);
        }

        $banner->update($data);

        return response()->json([
            'message' => 'تم تحديث البانر بنجاح',
            'data' => $banner->load('category'),
        ]);
    }

    /**
     * Delete banner
     */
    public function destroy(CategoryBanner $banner)
    {
        $banner->delete();

        return response()->json([
            'message' => 'تم حذف البانر',
        ]);
    }

    /**
     * Usage Report - Shows banners per category grouped by type
     */
    public function usageReport()
    {
        $categories = Category::withCount([
            'banners',
            'banners as home_banners_count' => function ($query) {
                $query->where('banner_type', CategoryBanner::TYPE_HOME_PAGE);
            },
            'banners as ad_creation_banners_count' => function ($query) {
                $query->where('banner_type', CategoryBanner::TYPE_AD_CREATION);
            },
        ])->get();

        return response()->json([
            'data' => $categories->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'total_banners' => $cat->banners_count,
                    'home_page_banners' => $cat->home_banners_count,
                    'ad_creation_banners' => $cat->ad_creation_banners_count,
                ];
            }),
        ]);
    }

    /**
     * Get banner by category slug and type (Public endpoint)
     */
    public function getByCategorySlug(string $categorySlug, Request $request)
    {
        $category = Category::where('slug', $categorySlug)->first();

        if (!$category) {
            return response()->json([
                'message' => 'القسم غير موجود',
            ], 404);
        }

        // Default to home_page if not specified
        $bannerType = $request->query('type', CategoryBanner::TYPE_HOME_PAGE);
        
        // Validate type
        if (!in_array($bannerType, [CategoryBanner::TYPE_HOME_PAGE, CategoryBanner::TYPE_AD_CREATION])) {
            $bannerType = CategoryBanner::TYPE_HOME_PAGE;
        }

        $banner = CategoryBanner::where('category_id', $category->id)
            ->where('banner_type', $bannerType)
            ->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->first();

        if (!$banner) {
            return response()->json([
                'message' => 'لا يوجد بانر متاح لهذا القسم',
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $banner->id,
                'banner_url' => $banner->banner_url,
                'category' => $category->name,
                'banner_type' => $banner->banner_type,
            ],
        ]);
    }

    /**
     * Get Home Page Banner (Public - Specific endpoint)
     */
    public function getHomePageBanner(string $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->first();

        if (!$category) {
            return response()->json([
                'message' => 'القسم غير موجود',
            ], 404);
        }

        $banner = CategoryBanner::where('category_id', $category->id)
            ->where('banner_type', CategoryBanner::TYPE_HOME_PAGE)
            ->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->first();

        if (!$banner) {
            return response()->json([
                'message' => 'لا يوجد بانر للصفحة الرئيسية في هذا القسم',
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $banner->id,
                'banner_url' => $banner->banner_url,
                'category' => $category->name,
            ],
        ]);
    }

    /**
     * Get Ad Creation Banner (Public - Specific endpoint)
     */
    public function getAdCreationBanner(string $categorySlug)
    {
        $category = Category::where('slug', $categorySlug)->first();

        if (!$category) {
            return response()->json([
                'message' => 'القسم غير موجود',
            ], 404);
        }

        $banner = CategoryBanner::where('category_id', $category->id)
            ->where('banner_type', CategoryBanner::TYPE_AD_CREATION)
            ->where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->first();

        if (!$banner) {
            return response()->json([
                'message' => 'لا يوجد بانر لصفحة إضافة الإعلان في هذا القسم',
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $banner->id,
                'banner_url' => $banner->banner_url,
                'category' => $category->name,
            ],
        ]);
    }
}
