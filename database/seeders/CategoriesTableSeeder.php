<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        // ترتيب بحسب الصورة (sort_order)
        $ordered = [
            ['slug' => 'real_estate',     'name' => 'العقارات',                    'icon' => 'العقارات.png'],
            ['slug' => 'cars',            'name' => 'السيارات',                    'icon' => 'السيارات.png'],
            ['slug' => 'cars_rent',       'name' => 'إيجار السيارات',              'icon' => 'ايجار السيارات.png'],
            ['slug' => 'spare-parts',     'name' => 'قطع غيار سيارات',            'icon' => 'قطع غيار سيارات.png'],

            ['slug' => 'stores',          'name' => 'المتاجر المولات',             'icon' => 'المتاجر المولات.png'],
            ['slug' => 'restaurants',     'name' => 'المطاعم',                     'icon' => 'المطاعم.png'],
            ['slug' => 'groceries',       'name' => 'محلات غذائيه',                  'icon' => 'محلات غذائيه.png'],

            ['slug' => 'food-products',   'name' => 'منتجات غذائيه',               'icon' => 'منتجات الغذائيه.png'],
            ['slug' => 'electronics',     'name' => 'الالكترونيات',                'icon' => 'الاكترونيات.png'],
            ['slug' => 'home-tools',      'name' => 'ادوات منزليه',                 'icon' => 'ادوات منزليه.png'],
            ['slug' => 'furniture',       'name' => 'اثاث ومفروشات',               'icon' => 'اثاث ومفروشات.png'],
            ['slug' => 'doctors',         'name' => 'الاطباء',                       'icon' => 'الاطباء.png'],
            ['slug' => 'health',          'name' => 'الصحه',                        'icon' => 'الصحه.png'],
            ['slug' => 'teachers',        'name' => 'المدرسين',                    'icon' => 'المدرسين.png'],
            ['slug' => 'education',       'name' => 'التعليم',                      'icon' => 'التعليم.png'],
            ['slug' => 'jobs',            'name' => 'الوظائف',                      'icon' => 'الوظائف.png'],
            ['slug' => 'shipping',        'name' => 'الشحن والتوصيل',               'icon' => 'الشحن التوصيل.png'],
            ['slug' => 'mens-clothes',    'name' => 'الملابس الرجاليه والاحذيه',    'icon' => 'الملابس الرجاليه الاحذيه.jpg'],
            ['slug' => 'watches-jewelry', 'name' => 'الساعات والمجوهرات',           'icon' => 'الساعات المجوهرات.png'],
            ['slug' => 'free-professions', 'name' => 'المهن الحره والخدمات',         'icon' => 'المهن الحره الخدمات.png'],
            ['slug' => 'kids-toys',       'name' => 'لعب مستلزمات اطفال',          'icon' => 'لعب مستلزمات الاطفال.png'],
            ['slug' => 'gym',             'name' => 'جيمات',                         'icon' => 'جيمات.png'],
            ['slug' => 'construction',    'name' => 'مواد البناء والتشطيب',         'icon' => 'مواد البناء والتشطيب.png'],
            ['slug' => 'maintenance',     'name' => 'الصيانه العامه',               'icon' => 'الصيانه العامه.png'],
            ['slug' => 'car-services',    'name' => 'خدمات صيانه السيارات',         'icon' => 'خدمات صيانه السيارات.png'],
            ['slug' => 'home-services',   'name' => 'خدمات صيانه المنازل',          'icon' => 'خدمات صيانه المنازل.png'],
            ['slug' => 'lighting-decor',  'name' => 'الإضاءه والديكور',             'icon' => 'الاضائه الديكور.png'],
            ['slug' => 'animals',         'name' => 'طيور وحيوانات',                'icon' => 'طيور حيوانات.png'],

            ['slug' => 'farm-products',   'name' => 'منتجات مزارع ومحاصيل',        'icon' => 'منتجات مزارع ومصانع.png'],
            ['slug' => 'wholesale',       'name' => 'بيع الجمله',                   'icon' => 'بيع الجمله.png'],
            ['slug' => 'production-lines', 'name' => 'مواد وخطوط الانتاج',           'icon' => 'مواد وخطوط الانتاج.png'],
            ['slug' => 'light-vehicles',  'name' => 'دراجات ومركبات',               'icon' => 'دراجات مركبات.png'],
            ['slug' => 'heavy-transport', 'name' => 'نقل معدات ثقيله',              'icon' => 'نقل معدات ثقيله.png'],

            ['slug' => 'tools',           'name' => 'عدد مستلزمات',                 'icon' => 'عدد مستلزمات.png'],
            ['slug' => 'home-appliances', 'name' => 'الاجهزه المنزليه',              'icon' => 'الاجهزة المنزليه.png'],
            ['slug' => 'missing',         'name' => 'مفقودين',                       'icon' => 'مفقودين.png'],
        ];

        // نضبط sort_order ونحدّث حسب slug
        foreach ($ordered as $index => $row) {
            $row['is_active'] = $row['is_active'] ?? true;
            if (!empty($row['icon'])) {
                $row['icon'] = str_replace(' ', '_', $row['icon']);
            }

            DB::table('categories')->updateOrInsert(
                ['slug' => $row['slug']],
                [
                    'name'       => $row['name'],
                    'icon'       => $row['icon'],
                    'is_active'  => $row['is_active'],
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
