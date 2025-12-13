<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Admin\StoreCategoryFieldRequest;
use App\Http\Requests\Admin\UpdateCategoryFieldRequest;
use App\Models\Category;
use App\Models\CategoryField;
use App\Models\Governorate;
use App\Models\Make;
use App\Support\Section;
use Illuminate\Http\Request;
use App\Models\CategoryMainSection;
use App\Models\CategorySubSection;


class CategoryFieldsController extends Controller
{
    // GET /api/admin/category-fields?category_slug=cars
    public function index(Request $request)
    {
        $q = CategoryField::query()
            ->orderBy('category_slug')
            ->orderBy('sort_order');

        $slug = $request->query('category_slug');

        if ($slug) {
            $q->where('category_slug', $slug);
        }

        $fields = $q->get();

        // Append "غير ذلك" to options if not present
        $fields->transform(function ($field) {
            if (!empty($field->options) && is_array($field->options)) {
                if (!in_array('غير ذلك', $field->options)) {
                    $options = $field->options;
                    $options[] = 'غير ذلك';
                    $field->options = $options;
                }
            }
            return $field;
        });

        $governorates = Governorate::with('cities')->get();

        $section = $slug ? Section::fromSlug($slug) : null;

        $supportsMakeModel = $section?->supportsMakeModel() ?? false;
        $supportsSections = $section?->supportsSections() ?? false; // ✅ جديد

        $makes = [];
        if ($supportsMakeModel) {
            $makes = Make::with('models')->get();
        }

        $mainSections = [];
        if ($supportsSections && $section) {
            $mainSections = CategoryMainSection::with([
                'subSections' => function ($q) {
                    $q->where('is_active', true)
                        ->orderBy('sort_order');
                }
            ])
                ->where('category_id', $section->id())   // 🟢 بس الكاتيجوري الحالي
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }

        return response()->json([
            'data' => $fields,

            'governorates' => $governorates,

            'makes' => $supportsMakeModel ? $makes : [],
            'supports_make_model' => $supportsMakeModel,

            // ✅ دعم الأقسام الرئيسية/الفرعية
            'supports_sections' => $supportsSections,
            'main_sections' => $mainSections, // جوّاها subSections جاهزة
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
        } else {
            if (!in_array('غير ذلك', $data['options'])) {
                $data['options'][] = 'غير ذلك';
            }
        }

        $field = CategoryField::create($data);

        return response()->json([
            'message' => 'تم إنشاء الحقل بنجاح',
            'data' => $field,
        ], 201);
    }

    // PUT /api/admin/category-fields/{id}
    public function update(UpdateCategoryFieldRequest $request, $categorySlug)
    {
        $data = $request->validated();

        $field = CategoryField::where('category_slug', $categorySlug)
            ->where('field_name', $data['field_name'])
            ->first();

        if (!$field) {
            throw ValidationException::withMessages([
                'field_name' => ['الحقل المطلوب غير موجود في هذا القسم.'],
            ]);
        }

        if (isset($data['options']) && is_array($data['options'])) {

            $clean = [];
            foreach ($data['options'] as $opt) {
                $value = trim((string) $opt);
                if ($value !== '') {
                    $clean[] = $value;
                }
            }

            $data['options'] = array_values(array_unique($clean));
            
            if (!empty($data['options']) && !in_array('غير ذلك', $data['options'])) {
                $data['options'][] = 'غير ذلك';
            }
        }

        unset($data['field_name']);

        $field->update($data);

        return response()->json([
            'message' => 'تم تحديث الحقل بنجاح',
            'data' => $field->fresh(),
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
