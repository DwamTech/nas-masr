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

        // الأساسيات
        $rows = [
            ['id' => 1, 'slug' => 'cars',        'name' => 'السيارات',        'icon' => 'السيارات.png',          'is_active' => true],
            ['id' => 2, 'slug' => 'cars_rent',   'name' => 'إيجار السيارات',  'icon' => 'ايجار السيارات.png',    'is_active' => true],
            ['id' => 3, 'slug' => 'real_estate', 'name' => 'العقارات',        'icon' => 'العقارات.png',          'is_active' => true],
            ['id' => 4, 'slug' => 'animals',     'name' => 'طيور وحيوانات',    'icon' => 'طيور حيوانات.png',     'is_active' => true],
            ['id' => 5, 'slug' => 'jobs',        'name' => 'الوظائف',          'icon' => 'الوظائف.png',           'is_active' => true],
        ];

        // الباقي زي اللي في الاسكرين
        $more = [
            ['slug' => 'furniture',         'name' => 'اثاث ومفروشات',             'icon' => 'اثاث ومفروشات.png'],
            ['slug' => 'home-tools',        'name' => 'ادوات منزليه',               'icon' => 'ادوات منزليه.png'],
            ['slug' => 'home-appliances',   'name' => 'الاجهزه المنزليه',            'icon' => 'الاجهزة المنزليه.png'],
            ['slug' => 'lighting-decor',    'name' => 'الإضاءه والديكور',           'icon' => 'الاضائه الديكور.png'],
            ['slug' => 'doctors',           'name' => 'الاطباء',                     'icon' => 'الاطباء.png'],
            ['slug' => 'electronics',       'name' => 'الالكترونيات',               'icon' => 'الاكترونيات.png'],
            ['slug' => 'education',         'name' => 'التعليم',                     'icon' => 'التعليم.png'],
            ['slug' => 'watches-jewelry',   'name' => 'الساعات والمجوهرات',         'icon' => 'الساعات المجوهرات.png'],
            ['slug' => 'shipping',          'name' => 'الشحن والتوصيل',             'icon' => 'الشحن التوصيل.png'],
            ['slug' => 'health',            'name' => 'الصحه',                       'icon' => 'الصحه.png'],
            ['slug' => 'maintenance',       'name' => 'الصيانه العامه',             'icon' => 'الصيانه العامه.png'],
            ['slug' => 'stores',            'name' => 'المتاجر المولات',           'icon' => 'المتاجر المولات.png'],
            ['slug' => 'teachers',          'name' => 'المدرسين',                   'icon' => 'المدرسين.png'],
            ['slug' => 'restaurants',       'name' => 'المطاعم',                    'icon' => 'المطاعم.png'],
            // دي اللي كانت عندك JPG
            ['slug' => 'mens-clothes',      'name' => 'الملابس الرجاليه والاحذيه',  'icon' => 'الملابس الرجاليه الاحذيه.jpg'],
            ['slug' => 'free-professions',  'name' => 'المهن الحره والخدمات',       'icon' => 'المهن الحره الخدمات.png'],
            ['slug' => 'wholesale',         'name' => 'بيع الجمله',                 'icon' => 'بيع الجمله.png'],
            ['slug' => 'car-services',      'name' => 'خدمات صيانه السيارات',       'icon' => 'خدمات صيانه السيارات.png'],
            ['slug' => 'home-services',     'name' => 'خدمات صيانه المنازل',        'icon' => 'خدمات صيانه المنازل.png'],
            ['slug' => 'light-vehicles',    'name' => 'دراجات ومركبات',             'icon' => 'دراجات مركبات.png'],
            ['slug' => 'tools',             'name' => 'عدد مستلزمات',               'icon' => 'عدد مستلزمات.png'],
            ['slug' => 'spare-parts',       'name' => 'قطع غيار سيارات',            'icon' => 'قطع غيار سيارات.png'],
            ['slug' => 'kids-toys',         'name' => 'لعب مستلزمات اطفال',         'icon' => 'لعب مستلزمات الاطفال.png'],
            ['slug' => 'groceries',         'name' => 'محلات غذائيه',               'icon' => 'محلات غذائيه.png'],
            ['slug' => 'missing',           'name' => 'مفقودين',                    'icon' => 'مفقودين.png'],
            ['slug' => 'food-products',     'name' => 'منتجات غذائيه',              'icon' => 'منتجات الغذائيه.png'],
            ['slug' => 'farm-products',     'name' => 'منتجات مزارع ومحاصيل',       'icon' => 'منتجات مزارع ومصانع.png'],
            ['slug' => 'production-lines',  'name' => 'مواد وخطوط الانتاج',          'icon' => 'مواد وخطوط الانتاج.png'],
            ['slug' => 'construction',      'name' => 'مواد البناء والتشطيب',        'icon' => 'مواد البناء والتشطيب.png'],
            ['slug' => 'heavy-transport',   'name' => 'نقل معدات ثقيله',            'icon' => 'نقل معدات ثقيله.png'],
        ];

        // نكمّل IDs
        $startId = count($rows) + 1;
        foreach ($more as $i => $row) {
            $row['id'] = $startId + $i;
            $row['is_active'] = $row['is_active'] ?? true;
            $rows[] = $row;
        }

        // هنا بقى السحر: نحول أي مسافة لـ _
        foreach ($rows as $row) {
            if (!empty($row['icon'])) {
                $row['icon'] = str_replace(' ', '_', $row['icon']);
            }

            DB::table('categories')->updateOrInsert(
                ['id' => $row['id']],
                [
                    'slug'       => $row['slug'],
                    'name'       => $row['name'],
                    'icon'       => $row['icon'],
                    'is_active'  => $row['is_active'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
