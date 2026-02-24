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

        // ✅ استخدام OptionsHelper مع الترتيب العكسي (Z to A)
        $fields = OptionsHelper::processFieldsCollection(
            $fields, 
            $shouldSort = true,      // نعم، نريد ترتيب
            $reverseSort = true      // نعم، ترتيب عكسي
        );

        // ✅ جلب المحافظات مع المدن وترتيبها عكسياً
        $governorates = Governorate::with('cities')->get();
        
        // ترتيب المحافظات والمدن عكسياً
        $governoratesArray = [];
        foreach ($governorates as $governorate) {
            $cityNames = $governorate->cities->pluck('name')->toArray();
            $governoratesArray[$governorate->name] = $cityNames;
        }
        
        // ترتيب المدن داخل كل محافظة عكسياً
        $governoratesArray = OptionsHelper::processOptionsMap(
            $governoratesArray,
            $shouldSort = true,
            $reverseSort = true
        );
        
        // ترتيب أسماء المحافظات نفسها عكسياً
        $governorateNames = array_keys($governoratesArray);
        $governorateNames = OptionsHelper::processOptions(
            $governorateNames,
            $shouldSort = true,
            $reverseSort = true
        );
        
        // إعادة ترتيب المصفوفة حسب المحافظات المرتبة
        $sortedGovernorates = [];
        foreach ($governorateNames as $govName) {
            if (isset($governoratesArray[$govName])) {
                $sortedGovernorates[$govName] = $governoratesArray[$govName];
            }
        }
        
        // تحويل للصيغة المطلوبة للفرونت إند
        $governorates = collect($sortedGovernorates)->map(function ($cities, $govName) {
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
            
            // ✅ معالجة الماركات والموديلات - ترتيب عكسي مع "غير ذلك" في الآخر
            $makesArray = [];
            foreach ($makes as $make) {
                $makesArray[$make->name] = $make->models->pluck('name')->toArray();
            }
            
            // ترتيب الموديلات داخل كل ماركة عكسياً
            $makesArray = OptionsHelper::processOptionsMap(
                $makesArray,
                $shouldSort = true,
                $reverseSort = true
            );
            
            // ترتيب أسماء الماركات نفسها عكسياً
            $makeNames = array_keys($makesArray);
            $makeNames = OptionsHelper::processOptions(
                $makeNames,
                $shouldSort = true,
                $reverseSort = true
            );
            
            // إعادة ترتيب المصفوفة حسب الماركات المرتبة
            $sortedMakesArray = [];
            foreach ($makeNames as $makeName) {
                if (isset($makesArray[$makeName])) {
                    $sortedMakesArray[$makeName] = $makesArray[$makeName];
                }
            }
            
            // تحويل للصيغة المطلوبة للفرونت إند
            $makes = collect($sortedMakesArray)->map(function ($models, $makeName) {
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
            
            // ✅ معالجة الأقسام الرئيسية والفرعية - ترتيب عكسي مع "غير ذلك" في الآخر
            $mainSectionsArray = [];
            foreach ($mainSections as $mainSection) {
                $subSectionNames = $mainSection->subSections->pluck('name')->toArray();
                $mainSectionsArray[$mainSection->name] = $subSectionNames;
            }
            
            // ترتيب الأقسام الفرعية داخل كل قسم رئيسي عكسياً
            $mainSectionsArray = OptionsHelper::processOptionsMap(
                $mainSectionsArray,
                $shouldSort = true,
                $reverseSort = true
            );
            
            // ترتيب أسماء الأقسام الرئيسية نفسها عكسياً
            $mainSectionNames = array_keys($mainSectionsArray);
            $mainSectionNames = OptionsHelper::processOptions(
                $mainSectionNames,
                $shouldSort = true,
                $reverseSort = true
            );
            
            // إعادة ترتيب المصفوفة حسب الأقسام الرئيسية المرتبة
            $sortedMainSections = [];
            foreach ($mainSectionNames as $mainName) {
                if (isset($mainSectionsArray[$mainName])) {
                    $sortedMainSections[$mainName] = $mainSectionsArray[$mainName];
                }
            }
            
            // تحويل للصيغة المطلوبة للفرونت إند
            $mainSections = collect($sortedMainSections)->map(function ($subSections, $mainName) {
                return [
                    'name' => $mainName,
                    'sub_sections' => collect($subSections)->map(function ($subName) {
                        return ['name' => $subName];
                    })->values()->all()
                ];
            })->values()->all();
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
            // ✅ معالجة مع الترتيب العكسي
            $data['options'] = OptionsHelper::processOptions(
                $data['options'],
                $shouldSort = true,
                $reverseSort = true
            );
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
            
            // ✅ استخدام OptionsHelper مع الترتيب العكسي
            $data['options'] = OptionsHelper::processOptions(
                $clean,
                $shouldSort = true,
                $reverseSort = true
            );
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
