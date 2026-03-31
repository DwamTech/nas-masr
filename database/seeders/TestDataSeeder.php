<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryMainSection;
use App\Models\CategorySubSection;
use App\Models\City;
use App\Models\Governorate;
use App\Models\Make;
use App\Models\CarModel;
use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * This seeder provides basic test data for guest system testing.
     * It creates essential categories, governorates, cities, makes, models, and sections.
     */
    public function run(): void
    {
        $this->seedCategories();
        $this->seedGovernoratesAndCities();
        $this->seedMakesAndModels();
        $this->seedCategorySections();
    }

    /**
     * Seed basic categories (cars, jobs, real-estate)
     */
    private function seedCategories(): void
    {
        $categories = [
            [
                'slug' => 'cars',
                'name' => 'السيارات',
                'icon' => 'السيارات.png',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => 'jobs',
                'name' => 'الوظائف',
                'icon' => 'الوظائف.png',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => 'real_estate',
                'name' => 'العقارات',
                'icon' => 'العقارات.png',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrInsert(
                ['slug' => $category['slug']],
                $category
            );
        }
    }

    /**
     * Seed governorates and cities
     */
    private function seedGovernoratesAndCities(): void
    {
        $govCities = [
            'القاهرة' => ['مدينة نصر', 'مصر الجديدة', 'حلوان', 'المعادي'],
            'الجيزة' => ['الدقي', '6 أكتوبر', 'الهرم', 'الشيخ زايد'],
            'الإسكندرية' => ['حي وسط الإسكندرية', 'العجمي', 'سموحة', 'برج العرب'],
        ];

        foreach ($govCities as $govName => $cityList) {
            $governorate = Governorate::firstOrCreate(
                ['name' => $govName],
                ['is_active' => true]
            );

            foreach ($cityList as $cityName) {
                City::firstOrCreate(
                    [
                        'governorate_id' => $governorate->id,
                        'name' => $cityName,
                    ],
                    ['is_active' => true]
                );
            }
        }
    }

    /**
     * Seed makes and models for automotive categories
     */
    private function seedMakesAndModels(): void
    {
        $makesModels = [
            'تويوتا' => ['كورولا', 'كامري', 'يارس', 'راف 4'],
            'هيونداي' => ['إلنترا', 'أكسنت', 'توسان', 'سوناتا'],
            'كيا' => ['سيراتو', 'سبورتاج', 'بيكانتو', 'كارنفال'],
        ];

        foreach ($makesModels as $makeName => $models) {
            $make = Make::firstOrCreate(
                ['name' => $makeName],
                ['is_active' => true]
            );

            foreach ($models as $modelName) {
                CarModel::firstOrCreate(
                    [
                        'make_id' => $make->id,
                        'name' => $modelName,
                    ],
                    ['is_active' => true]
                );
            }
        }
    }

    /**
     * Seed main sections and sub sections for categories
     */
    private function seedCategorySections(): void
    {
        $sections = [
            'cars' => [
                'سيارات ركوب' => ['سيدان', 'هاتشباك', 'كوبيه', 'SUV'],
                'سيارات تجارية' => ['ميني باص', 'نقل خفيف', 'نقل ثقيل'],
            ],
            'jobs' => [
                'إدارة وسكرتارية' => ['مدير موارد بشرية', 'مدير مكتب', 'سكرتارية', 'موظف إداري'],
                'مبيعات وتسويق' => ['مندوب مبيعات', 'مدير مبيعات', 'مسوق رقمي', 'أخصائي تسويق'],
            ],
            'real_estate' => [
                'شقق' => ['شقة للبيع', 'شقة للإيجار', 'شقة مفروشة', 'استوديو'],
                'فيلات' => ['فيلا للبيع', 'فيلا للإيجار', 'فيلا مستقلة', 'تاون هاوس'],
            ],
        ];

        foreach ($sections as $categorySlug => $mainSubs) {
            $category = Category::where('slug', $categorySlug)->first();

            if (!$category) {
                continue;
            }

            $mainSort = 1;

            foreach ($mainSubs as $mainName => $subList) {
                $main = CategoryMainSection::updateOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $mainName,
                    ],
                    [
                        'sort_order' => $mainSort++,
                        'is_active' => true,
                    ]
                );

                $subOrder = 1;
                foreach ($subList as $subName) {
                    CategorySubSection::firstOrCreate(
                        [
                            'category_id' => $category->id,
                            'main_section_id' => $main->id,
                            'name' => $subName,
                        ],
                        [
                            'sort_order' => $subOrder++,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
