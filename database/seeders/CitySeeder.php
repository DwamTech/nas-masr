<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // اسم المحافظة => مصفوفة المدن/المراكز التابعة لها
        $govCities = [
            'القاهرة' => [
                'مدينة نصر',
                'مصر الجديدة',
                'حلوان',
                'المعادي',
            ],
            'الجيزة' => [
                'الدقي',
                '6 أكتوبر',
                'الهرم',
                'الشيخ زايد',
            ],
            'الإسكندرية' => [
                'حي وسط الإسكندرية',
                'العجمي',
                'سموحة',
                'برج العرب',
            ],
            'الدقهلية' => [
                'المنصورة',
                'ميت غمر',
                'طلخا',
                'السنبلاوين',
            ],
            'الشرقية' => [
                'الزقازيق',
                'العاشر من رمضان',
                'بلبيس',
                'منيا القمح',
            ],
            'القليوبية' => [
                'بنها',
                'شبرا الخيمة',
                'قليوب',
                'الخانكة',
            ],
            'أسوان' => [
                'أسوان',
                'إدفو',
                'كوم أمبو',
                'دراو',
            ],
            'السويس' => [
                'السويس',
                'الجناين',
                'عتاقة',
                'فيصل',
            ],
        ];

        foreach ($govCities as $govName => $cities) {
            $gov = Governorate::where('name', $govName)->first();

            if (!$gov) {
                // لو المحافظة مش موجودة في جدول المحافظات نطنشها
                continue;
            }

            $rows = [];
            foreach ($cities as $cityName) {
                $rows[] = [
                    'name'            => $cityName,
                    'governorate_id'  => $gov->id,
                ];
            }

            City::insert($rows);
        }
    }

}
