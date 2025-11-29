<?php

namespace Database\Seeders;

use App\Models\CategoryField;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoryFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $realEstateFields = [
            [
                'category_slug' => 'real_estate',
                'field_name' => 'property_type',
                'display_name' => 'نوع العقار',
                'type' => 'string',
                'options' => ['فيلا', 'شقة', 'أرض', 'استوديو', 'محل تجاري', 'مكتب', 'أخرى'],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'real_estate',
                'field_name' => 'contract_type',
                'display_name' => 'نوع العقد',
                'type' => 'string',
                'options' => ['بيع', 'إيجار'],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];
        $carsRentFields = [
            [
                'category_slug' => 'cars_rent',
                'field_name' => 'year',
                'display_name' => 'السنة',
                'type' => 'string',
                'options' => range(2000, 2030),
                'required' => true,
                'filterable' => true,
                'sort_order' => 3,
            ],
            [
                'category_slug' => 'cars_rent',
                'field_name' => 'driver_option',
                'display_name' => 'السائق',
                'type' => 'string',
                'options' => ['بدون سائق', 'بسائق'],
                'required' => true,
                'filterable' => true,
                'sort_order' => 4,
            ],
        ];
        $animalsFields = [
            [
                'category_slug' => 'animals',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => ['طيور', 'حيوانات أليفة', 'إكسسوارات', 'أعلاف', 'أخرى'],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'animals',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => ['ببغاء', 'حمام', 'قطط', 'كلاب', 'أسماك', 'أرانب', 'معدات تربية'],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];
        $jobsFields = [
            [
                'category_slug' => 'jobs',
                'field_name' => 'job_category',
                'display_name' => 'التصنيف',
                'type' => 'string',
                'options' => [
                    'إدارة',
                    'محاسبة ومالية',
                    'مبيعات',
                    'تسويق',
                    'تكنولوجيا معلومات',
                    'تعليم وتدريب',
                    'طب وتمريض',
                    'خدمة عملاء',
                    'حرف وصناعات',
                    'سياحة وفنادق',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'jobs',
                'field_name' => 'specialization',
                'display_name' => 'التخصص',
                'type' => 'string',
                'options' => [
                    'محاسب',
                    'مسؤول موارد بشرية',
                    'مندوب مبيعات',
                    'مسوّق رقمي',
                    'مبرمج',
                    'مصمم جرافيك',
                    'مدرس',
                    'ممرض',
                    'سكرتارية',
                    'سائق',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
            [
                'category_slug' => 'jobs',
                'field_name' => 'salary',
                'display_name' => 'الراتب',
                'type' => 'decimal',    // أو int حسب ما تحبي
                'options' => [],           // مفيش اختيارات
                'required' => true,
                'filterable' => false,
                'rules_json' => ['min:0'],    // عشان مايدخلش رقم سالب
                'sort_order' => 3,
            ],
            [
                'category_slug' => 'jobs',
                'field_name' => 'contact_via',
                'display_name' => 'التواصل عبر',
                'type' => 'string',
                'options' => [],           // برضه حر
                'required' => true,
                'filterable' => false,        // مش لازم أفلتر بيه
                'sort_order' => 4,
            ],
        ];


        $carFields = [
            [
                'category_slug' => 'cars',
                'field_name' => 'year',
                'display_name' => 'السنة',
                'type' => 'string',
                'options' => range(1990, 2025),
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'cars',
                'field_name' => 'kilometers',
                'display_name' => 'الكيلو متر',
                'type' => 'string',
                'options' => [
                    '0 - 10،000',
                    '10،000 - 50،000',
                    '50،000 - 100،000',
                    '100،000 - 200،000',
                    'أكثر من 200،000',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
            [
                'category_slug' => 'cars',
                'field_name' => 'fuel_type',
                'display_name' => 'نوع الوقود',
                'type' => 'string',
                'options' => ['بنزين', 'ديزل', 'غاز', 'كهرباء', 'أخرى'],
                'required' => true,
                'filterable' => true,
                'sort_order' => 3,
            ],
            [
                'category_slug' => 'cars',
                'field_name' => 'transmission',
                'display_name' => 'الفتيس',
                'type' => 'string',
                'options' => ['أوتوماتيك', 'مانيوال'],
                'required' => true,
                'filterable' => true,
                'sort_order' => 4,
            ],
            [
                'category_slug' => 'cars',
                'field_name' => 'exterior_color',
                'display_name' => 'اللون الخارجي',
                'type' => 'string',
                'options' => ['أبيض', 'أسود', 'أزرق', 'رمادي', 'فضي', 'أحمر', 'أخرى'],
                'required' => true,
                'filterable' => true,
                'sort_order' => 5,
            ],
            [
                'category_slug' => 'cars',
                'field_name' => 'type',
                'display_name' => 'النوع',
                'type' => 'string',
                'options' => [
                    'سيدان',
                    'هاتشباك',
                    'SUV',
                    'كروس أوفر',
                    'بيك أب',
                    'كوبيه',
                    'كشف',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 6,
            ],
        ];
        // ========================
        // المتاجر والمولات - stores
        // ========================
        $storesFields = [
            [
                'category_slug' => 'stores',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'متاجر',
                    'مولات تجارية',
                    'أسواق شعبية',
                    'معارض',
                    'أكشاك ومحلات صغيرة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'stores',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'سوبر ماركت',
                    'هايبر ماركت',
                    'متجر ملابس',
                    'متجر أحذية',
                    'متجر إلكترونيات',
                    'متجر أدوات منزلية',
                    'متجر عطور وكماليات',
                    'متجر كتب وأدوات مكتبية',
                    'متجر مستحضرات تجميل',
                    'محل هدايا',
                    'معرض أثاث',
                    'معرض سيارات',
                    'محل ألعاب أطفال',
                    'محل حيوانات أليفة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // المطاعم - restaurants
        // ========================
        $restaurantsFields = [
            [
                'category_slug' => 'restaurants',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'مطاعم وجبات سريعة',
                    'مطاعم شرقية',
                    'مطاعم غربية',
                    'مطاعم أسماك',
                    'كافيهات',
                    'حلويات ومخابز',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'restaurants',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'شاورما',
                    'برجر',
                    'بيتزا',
                    'كشري',
                    'مشويات',
                    'فول وطعمية',
                    'مأكولات بحرية',
                    'مطعم عائلي',
                    'كافيه',
                    'محل آيس كريم',
                    'مخبز',
                    'محل حلويات شرقية',
                    'محل حلويات غربية',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // محلات غذائية - groceries
        // ========================
        $groceriesFields = [
            [
                'category_slug' => 'groceries',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'بقالة',
                    'سوبر ماركت',
                    'مخزن جملة',
                    'محل خضار وفاكهة',
                    'محل لحوم ودواجن',
                    'محل جبن وألبان',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'groceries',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'خضار',
                    'فاكهة',
                    'لحوم حمراء',
                    'دواجن',
                    'أسماك طازجة',
                    'جبن',
                    'ألبان',
                    'بيض',
                    'بقوليات',
                    'معلبات',
                    'زيوت وسمن',
                    'منظفات',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // الأجهزة المنزلية - home-appliances
        // ========================
        $homeAppliancesFields = [
            [
                'category_slug' => 'home-appliances',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'أجهزة تبريد وتكييف',
                    'أجهزة غسيل وتنظيف',
                    'أجهزة طبخ',
                    'أجهزة منزلية صغيرة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'home-appliances',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'ثلاجة',
                    'ديب فريزر',
                    'غسالة ملابس',
                    'غسالة أطباق',
                    'مكيف هواء',
                    'بوتاجاز',
                    'فرن كهربائي',
                    'ميكروويف',
                    'شاشة تلفزيون',
                    'مكنسة كهربائية',
                    'خلاط',
                    'محضر طعام',
                    'مروحة',
                    'دفاية',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // أدوات منزلية - home-tools
        // ========================
        $homeToolsFields = [
            [
                'category_slug' => 'home-tools',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'أدوات مطبخ',
                    'أدوات تنظيف',
                    'أدوات تنظيم وتخزين',
                    'أدوات حمام',
                    'ديكور منزلي بسيط',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'home-tools',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'أواني طبخ',
                    'أطباق وأكواب',
                    'أدوات تقديم',
                    'سلال تنظيم',
                    'صناديق تخزين',
                    'مفارش',
                    'ستائر',
                    'سجاد صغير',
                    'أدوات تنظيف',
                    'مكانس يدوية',
                    'مماسح أرضيات',
                    'إكسسوارات حمام',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // أثاث ومفروشات - furniture
        // ========================
        $furnitureFields = [
            [
                'category_slug' => 'furniture',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'غرف نوم',
                    'غرف معيشة',
                    'غرف أطفال',
                    'سفر وطعام',
                    'مكاتب',
                    'مفروشات',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'furniture',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'سرير',
                    'دولاب',
                    'كومود',
                    'ترابيزة سفرة',
                    'ترابيزة وسط',
                    'كنبة',
                    'أنتريه',
                    'ركنة',
                    'مكتبة',
                    'مكتب',
                    'كرسي مكتب',
                    'مراتب',
                    'سجاد',
                    'ستائر',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // الصحة - health
        // ========================
        $healthFields = [
            [
                'category_slug' => 'health',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'مراكز طبية',
                    'معامل تحاليل',
                    'صيدليات',
                    'مراكز علاج طبيعي',
                    'مراكز تجميل وعناية',
                    'عيادات متخصصة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'health',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'مركز أشعة',
                    'معمل تحاليل',
                    'صيدلية',
                    'مركز علاج طبيعي',
                    'مركز تخسيس وتغذية',
                    'مركز تجميل',
                    'مركز أسنان',
                    'مركز عيون',
                    'مركز قلب',
                    'مركز غسيل كلوي',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // التعليم - education
        // ========================
        $educationFields = [
            [
                'category_slug' => 'education',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'مدارس',
                    'جامعات ومعاهد',
                    'مراكز تدريب',
                    'حضانات',
                    'منصات تعليمية',
                    'دروس خصوصية',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'education',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'حضانة',
                    'مدرسة لغات',
                    'مدرسة حكومية',
                    'جامعة خاصة',
                    'جامعة حكومية',
                    'مركز لغات',
                    'مركز كمبيوتر',
                    'مركز تقوية دراسية',
                    'أكاديمية تدريب',
                    'دورات أونلاين',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // الشحن والتوصيل - shipping
        // ========================
        $shippingFields = [
            [
                'category_slug' => 'shipping',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'شركات شحن داخلي',
                    'شركات شحن دولي',
                    'خدمات توصيل طلبات',
                    'مندوب توصيل فردي',
                    'نقل بضائع ثقيلة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'shipping',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'توصيل داخل المدينة',
                    'توصيل بين المحافظات',
                    'شحن دولي جوي',
                    'شحن بحري',
                    'شحن بري',
                    'توصيل مطاعم',
                    'توصيل طرود وشحنات',
                    'نقل أثاث',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // الساعات والمجوهرات - watches-jewelry
        // ========================
        $watchesJewelryFields = [
            [
                'category_slug' => 'watches-jewelry',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'ساعات',
                    'مجوهرات ذهب',
                    'مجوهرات فضة',
                    'إكسسوارات',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'watches-jewelry',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'ساعة يد رجالي',
                    'ساعة يد حريمي',
                    'حلق',
                    'خاتم',
                    'سلسلة',
                    'أسورة',
                    'خلخال',
                    'دبلة زواج',
                    'طقم كامل',
                    'إكسسوارات تقليد',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // المهن الحرة والخدمات - free-professions
        // ========================
        $freeProfessionsFields = [
            [
                'category_slug' => 'free-professions',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'خدمات تقنية',
                    'خدمات تصميم وإعلان',
                    'خدمات قانونية ومالية',
                    'خدمات حرفية',
                    'استشارات وتدريب',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'free-professions',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'مبرمج حر',
                    'مصمم جرافيك',
                    'مسوّق إلكتروني',
                    'كاتب محتوى',
                    'مصور',
                    'محامي',
                    'محاسب قانوني',
                    'مستشار إداري',
                    'مدرب',
                    'فني صيانة',
                    'سباك',
                    'كهربائي',
                    'نجار',
                    'حداد',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // مستلزمات ولعب الأطفال - kids-toys
        // ========================
        $kidsToysFields = [
            [
                'category_slug' => 'kids-toys',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'ملابس أطفال',
                    'أحذية أطفال',
                    'ألعاب تعليمية',
                    'ألعاب ترفيهية',
                    'مستلزمات حديثي الولادة',
                    'أثاث أطفال',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'kids-toys',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'ملابس مواليد',
                    'ملابس أولاد',
                    'ملابس بنات',
                    'أحذية',
                    'عربات أطفال',
                    'سرير أطفال',
                    'كرسي طعام',
                    'ألعاب تركيب',
                    'ألعاب ذكاء',
                    'عرائس',
                    'سيارات ألعاب',
                    'دراجات أطفال',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // جيمات - gym
        // ========================
        $gymFields = [
            [
                'category_slug' => 'gym',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'صالات جيم للرجال',
                    'صالات جيم للسيدات',
                    'صالات مختلطة',
                    'مراكز تدريب شخصي',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'gym',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'جيم عام',
                    'كمال أجسام',
                    'جيم لياقة',
                    'ستوديو زومبا',
                    'ستوديو يوجا',
                    'تدريب شخصي',
                    'كروس فيت',
                    'جيم فندقي',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // مواد البناء والتشطيبات - construction
        // ========================
        $constructionFields = [
            [
                'category_slug' => 'construction',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'مواد بناء',
                    'تشطيبات داخلية',
                    'تشطيبات خارجية',
                    'أعمال عزل',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'construction',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'أسمنت',
                    'طوب',
                    'حديد تسليح',
                    'رمل وزلط',
                    'سيراميك وبورسلين',
                    'دهانات',
                    'أسقف معلقة',
                    'ورق حائط',
                    'ألمنيوم وواجهات زجاج',
                    'أبواب خشب',
                    'أبواب حديد',
                    'عوازل حرارية',
                    'عوازل رطوبة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // الصيانة العامة - maintenance
        // ========================
        $maintenanceFields = [
            [
                'category_slug' => 'maintenance',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'صيانة كهرباء',
                    'صيانة سباكة',
                    'صيانة أجهزة منزلية',
                    'صيانة إلكترونيات',
                    'صيانة مباني',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'maintenance',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'فني كهرباء',
                    'فني سباكة',
                    'فني تكييف',
                    'فني ثلاجات',
                    'فني غسالات',
                    'فني تلفزيونات',
                    'فني كمبيوتر',
                    'فني شبكات',
                    'صيانة مصاعد',
                    'عمال صيانة عامة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // خدمات وصيانة السيارات - car-services
        // ========================
        $carServicesFields = [
            [
                'category_slug' => 'car-services',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'ميكانيكا',
                    'كهرباء سيارات',
                    'زيوت وفلتر',
                    'كاوتش وجنوط',
                    'سمكرة ودهان',
                    'غسيل وتلميع',
                    'إكسسوارات وتركيب',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'car-services',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'مركز خدمة متكامل',
                    'ميكانيكي',
                    'فني كهرباء سيارات',
                    'تغيير زيوت',
                    'غيار فلاتر',
                    'تركيب إطارات',
                    'ترصيص وزوايا',
                    'سمكري',
                    'دهان سيارات',
                    'مغسلة سيارات',
                    'تلميع داخلي وخارجي',
                    'حماية طلاء',
                    'تركيب كماليات',
                    'تظليل',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // الإضاءة والديكور - lighting-decor
        // ========================
        $lightingDecorFields = [
            [
                'category_slug' => 'lighting-decor',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'إضاءة داخلية',
                    'إضاءة خارجية',
                    'إضاءة ديكورية',
                    'ديكور جبس',
                    'ديكور خشبي',
                    'إكسسوارات ديكور',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'lighting-decor',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'نجف',
                    'أباليك',
                    'سبوت لايت',
                    'شرائط ليد',
                    'لمبات',
                    'كشافات خارجية',
                    'وحدات إنارة حائطية',
                    'أسقف جبس',
                    'ديكور جبس 3D',
                    'لوحات ديكور',
                    'تحف وهدايا',
                    'مرايات',
                    'مزاهر',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // منتجات مزارع ومصانع - farm-products
        // ========================
        $farmProductsFields = [
            [
                'category_slug' => 'farm-products',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'منتجات ألبان',
                    'منتجات لحوم',
                    'منتجات دواجن',
                    'منتجات زراعية',
                    'منتجات غذائية مصنّعة',
                    'منتجات تنظيف وتعقيم',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'farm-products',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'ألبان طازجة',
                    'أجبان',
                    'لحوم مبردة',
                    'لحوم مجمدة',
                    'دواجن مبردة',
                    'دواجن مجمدة',
                    'بيض مزارع',
                    'خضروات مزارع',
                    'فاكهة مزارع',
                    'مخللات',
                    'مربّات',
                    'عصائر',
                    'مواد تنظيف',
                    'مطهرات',
                    'مناديل ورقية',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // بيع الجملة - wholesale
        // ========================
        $wholesaleFields = [
            [
                'category_slug' => 'wholesale',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'مواد غذائية جملة',
                    'ملابس وأحذية جملة',
                    'إلكترونيات جملة',
                    'أدوات منزلية جملة',
                    'مواد بناء جملة',
                    'منظفات جملة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'wholesale',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'توزيع سوبر ماركت',
                    'توزيع مطاعم',
                    'ملابس رجالي جملة',
                    'ملابس حريمي جملة',
                    'ملابس أطفال جملة',
                    'أحذية جملة',
                    'جملة موبايلات',
                    'جملة إكسسوارات موبايل',
                    'جملة أدوات منزلية',
                    'جملة بلاستيك',
                    'جملة منظفات',
                    'جملة مواد خام',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // مواد وخطوط الإنتاج - production-lines
        // ========================
        $productionLinesFields = [
            [
                'category_slug' => 'production-lines',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'خطوط إنتاج غذائية',
                    'خطوط إنتاج بلاستيك',
                    'خطوط إنتاج كرتون وورق',
                    'خطوط إنتاج أدوية ومستحضرات',
                    'خطوط إنتاج نسيج وملابس',
                    'معدات تغليف وتعبئة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'production-lines',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'خط إنتاج شيبسي',
                    'خط إنتاج بقوليات',
                    'خط إنتاج مخبوزات',
                    'خط إنتاج عصائر',
                    'ماكينات تغليف',
                    'ماكينات تعبئة أوتوماتيك',
                    'ماكينات تعبئة نصف أوتوماتيك',
                    'ماكينات نفخ بلاستيك',
                    'ماكينات طباعة',
                    'ماكينات خياطة صناعية',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // دراجات ومركبات خفيفة - light-vehicles
        // ========================
        $lightVehiclesFields = [
            [
                'category_slug' => 'light-vehicles',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'دراجات هوائية',
                    'دراجات نارية',
                    'سكوترات',
                    'مركبات كهربائية خفيفة',
                    'مركبات نقل خفيفة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'light-vehicles',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'عجلة أطفال',
                    'دراجة سباق',
                    'دراجة جبلية',
                    'موتوسيكل',
                    'اسكوتر بنزين',
                    'اسكوتر كهرباء',
                    'توك توك',
                    'ميني كار',
                    'جولف كار',
                    'تروسيكل',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // نقل ومعدات ثقيلة - heavy-transport
        // ========================
        $heavyTransportFields = [
            [
                'category_slug' => 'heavy-transport',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'شاحنات نقل',
                    'معدات إنشائية',
                    'معدات زراعية ثقيلة',
                    'معدات مناولة وتخزين',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'heavy-transport',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'سيارة نقل خفيف',
                    'سيارة نقل ثقيل',
                    'قلاب',
                    'تريلا',
                    'لودر',
                    'حفار',
                    'بلدوزر',
                    'رافعة شوكية',
                    'ونش',
                    'جرار زراعي',
                    'معدات زراعية أخرى',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // عدد ومستلزمات - tools
        // ========================
        $toolsFields = [
            [
                'category_slug' => 'tools',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'عدد يدوية',
                    'عدد كهربائية',
                    'عدد صناعية',
                    'عدد بناء وتشطيب',
                    'عدد ميكانيكا وسيارات',
                    'مستلزمات ورش',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'tools',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'مفكات ومفاتيح',
                    'مطرقة',
                    'مثقاب',
                    'صاروخ',
                    'مقصات وقطّاعات',
                    'معدات لحام',
                    'سلم',
                    'معدات سلامة',
                    'عدة ميكانيكي',
                    'عدة سباك',
                    'أدوات نجارة',
                    'أدوات حدادة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];

        // ========================
        // مفقودين - missing
        // ========================
        $missingFields = [
            [
                'category_slug' => 'missing',
                'field_name' => 'main_category',
                'display_name' => 'القسم الرئيسي',
                'type' => 'string',
                'options' => [
                    'أشخاص مفقودين',
                    'أطفال مفقودين',
                    'أشياء مفقودة',
                    'حيوانات مفقودة',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 1,
            ],
            [
                'category_slug' => 'missing',
                'field_name' => 'sub_category',
                'display_name' => 'القسم الفرعي',
                'type' => 'string',
                'options' => [
                    'طفل',
                    'رجل',
                    'امرأة',
                    'هوية شخصية',
                    'أوراق رسمية',
                    'موبايل',
                    'محفظة',
                    'مفاتيح',
                    'حيوان أليف',
                    'أخرى',
                ],
                'required' => true,
                'filterable' => true,
                'sort_order' => 2,
            ],
        ];


        $allFields = array_merge(
            $realEstateFields,
            $carFields,
            $carsRentFields,
            $animalsFields,
            $jobsFields,
            // $foodProductsFields,
            // $homeServicesFields,
            // $electronicsFields,
            // $mensClothesFields,
            // حط الجديد هنا
            $storesFields,
            $restaurantsFields,
            $groceriesFields,
            $homeAppliancesFields,
            $homeToolsFields,
            $furnitureFields,
            $healthFields,
            $educationFields,
            $shippingFields,
            $watchesJewelryFields,
            $freeProfessionsFields,
            $kidsToysFields,
            $gymFields,
            $constructionFields,
            $maintenanceFields,
            $carServicesFields,
            $lightingDecorFields,
            $farmProductsFields,
            $wholesaleFields,
            $productionLinesFields,
            $lightVehiclesFields,
            $heavyTransportFields,
            $toolsFields,
            $missingFields,
        );

        foreach ($allFields as $field) {
            CategoryField::updateOrCreate(
                [
                    'category_slug' => $field['category_slug'],
                    'field_name' => $field['field_name'],
                ],
                [
                    'display_name' => $field['display_name'],
                    'type' => $field['type'] ?? 'string',
                    'options' => $field['options'] ?? [],
                    'required' => $field['required'] ?? true,
                    'filterable' => $field['filterable'] ?? true,
                    'is_active' => true,
                    'sort_order' => $field['sort_order'] ?? 999,
                ]
            );
        }
    }
}
