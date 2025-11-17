<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryFieldRequest;
use App\Http\Requests\Admin\UpdateCategoryFieldRequest;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Governorate;
use App\Models\Make;
use App\Support\Section;
use Illuminate\Http\Request;

class CategoryFieldsController extends Controller
{
    // GET /api/admin/category-fields?category_slug=cars
    public function index(Request $request)
    {
        $q = CategoryField::query()
            ->orderBy('category_slug')
            ->orderBy('sort_order');

        if ($slug = $request->query('category_slug')) {
            $q->where('category_slug', $slug);
        }

        $fields = $q->get();

        $governorates = Governorate::with('cities')->get();

        $section = $slug ? Section::fromSlug($slug) : null;

        $supportsMakeModel = $section?->supportsMakeModel() ?? false;

        $makes = [];
        if ($supportsMakeModel) {
            $makes = Make::with('models')->get();
        }

        return response()->json([
            'data' => $fields,

            'governorates' => $governorates,

            'makes' => $supportsMakeModel ? $makes : [],

            'supports_make_model' => $supportsMakeModel,
        ]);
    }


    // POST /api/admin/category-fields
    public function store(StoreCategoryFieldRequest $request)
    {
        $data = $request->validated();

        $category = Category::firstOrCreate(
            ['slug' => $data['category_slug']],
            [
                'name' => $data['category_slug'],
                'is_active' => true,
            ]
        );
        if (empty($data['options'])) {
            $data['options'] = [];
        }

        $field = CategoryField::create($data);

        return response()->json([
            'message' => 'تم إنشاء الحقل بنجاح',
            'data' => $field,
        ], 201);
    }

    // PUT /api/admin/category-fields/{id}
    public function update(UpdateCategoryFieldRequest $request, CategoryField $categoryField)
    {
        $data = $request->validated();

        if (isset($data['options']) && empty($data['options'])) {
            $data['options'] = [];
        }

        $categoryField->update($data);

        return response()->json([
            'message' => 'تم تحديث الحقل بنجاح',
            'data' => $categoryField,
        ]);
    }

    public function destroy(CategoryField $categoryField)
    {
        $categoryField->update(['is_active' => false]);

        return response()->json([
            'message' => 'تم إلغاء تفعيل الحقل',
        ]);
    }
}
