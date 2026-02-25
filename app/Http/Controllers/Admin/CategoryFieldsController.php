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
use App\Support\OptionsHelper;
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

        // معالجة الحقول لضمان "غير ذلك" في الآخر (بدون ترتيب - سيتم الترتيب في الفرونت إند)
        $fields = OptionsHelper::processFieldsCollection($fields, false, false);

        // جلب المحافظات مع المدن
        $governorates = Governorate::with('cities')->get();
        
        // تحويل للصيغة المطلوبة (الترتيب سيتم في الفرونت إند)
        $governoratesArray = [];
        foreach ($governorates as $governorate) {
            $cityNames = $governorate->cities->pluck('name')->toArray();
            // معالجة المدن لضمان "غير ذلك" في الآخر
            $governoratesArray[$governorate->name] = OptionsHelper::processOptions($cityNames, false, false);
        }
        
        // تحويل للصيغة المطلوبة للفرونت إند
        $governorates = collect($governoratesArray)->map(function ($cities, $govName) {
            return [
                'name' => $govName,
                'cities' => collect($cities)->map(function ($cityName) {
                    return ['name' => $cityName];
                })->values()->all()
            ];
        })->values()->all();

        $section = $slug ? Section::fromSlug($slug) : null;

        $supportsMakeModel = $section?->supportsMakeModel() ?? false;
        $supportsSections = $section?->supportsSections() ?? false; // ✅ جديد

        $makes = [];
        if ($supportsMakeModel) {
            $makes = Make::with('models')->get();
            
            // معالجة الماركات والموديلات (الترتيب سيتم في الفرونت إند)
            $makesArray = [];
            foreach ($makes as $make) {
                $modelNames = $make->models->pluck('name')->toArray();
                // معالجة الموديلات لضمان "غير ذلك" في الآخر
                $makesArray[$make->name] = OptionsHelper::processOptions($modelNames, false, false);
            }
            
            // تحويل للصيغة المطلوبة للفرونت إند
            $makes = collect($makesArray)->map(function ($models, $makeName) {
                return [
                    'name' => $makeName,
                    'models' => collect($models)->map(function ($modelName) {
                        return ['name' => $modelName];
                    })->values()->all()
                ];
            })->values()->all();
        }

        $mainSections = [];
        if ($supportsSections && $section) {
            $mainSections = CategoryMainSection::with([
                'subSections' => function ($q) {
                    $q->where('is_active', true)
                        ->orderBy('sort_order');
                }
            ])
                ->where('category_id', $section->id())
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();
            
            // تحويل للصيغة المطلوبة للفرونت إند مع الـ IDs
            $mainSections = $mainSections->map(function ($mainSection) {
                $subSections = $mainSection->subSections->map(function ($subSection) {
                    return [
                        'id' => $subSection->id,
                        'name' => $subSection->name,
                        'title' => $subSection->title,
                    ];
                })->values();
                
                // معالجة الأقسام الفرعية لضمان "غير ذلك" في الآخر
                $subSectionNames = $subSections->pluck('name')->toArray();
                $processedNames = OptionsHelper::processOptions($subSectionNames, false, false);
                
                // إعادة ترتيب الأقسام الفرعية حسب الترتيب المعالج
                $orderedSubSections = collect($processedNames)->map(function ($name) use ($subSections) {
                    return $subSections->firstWhere('name', $name);
                })->filter()->values();
                
                return [
                    'id' => $mainSection->id,
                    'name' => $mainSection->name,
                    'title' => $mainSection->title,
                    'sub_sections' => $orderedSubSections->all()
                ];
            })->values();
            
            // معالجة الأقسام الرئيسية لضمان "غير ذلك" في الآخر
            $mainSectionNames = $mainSections->pluck('name')->toArray();
            $processedMainNames = OptionsHelper::processOptions($mainSectionNames, false, false);
            
            // إعادة ترتيب الأقسام الرئيسية حسب الترتيب المعالج
            $mainSections = collect($processedMainNames)->map(function ($name) use ($mainSections) {
                return $mainSections->firstWhere('name', $name);
            })->filter()->values()->all();
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
            $data['options'] = [OptionsHelper::OTHER_OPTION];
        } else {
            // معالجة لضمان "غير ذلك" في الآخر (بدون ترتيب)
            $data['options'] = OptionsHelper::processOptions($data['options'], false, false);
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
            // تنظيف وإزالة التكرار
            $clean = [];
            foreach ($data['options'] as $opt) {
                $value = trim((string) $opt);
                if ($value !== '') {
                    $clean[] = $value;
                }
            }

            $clean = array_values(array_unique($clean));
            
            // معالجة لضمان "غير ذلك" في الآخر (بدون ترتيب)
            $data['options'] = OptionsHelper::processOptions($clean, false, false);
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
