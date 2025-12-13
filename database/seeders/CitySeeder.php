<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        // المحافظات والمدن التابعة
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
            'غير ذلك' => [], // محافظة "غير ذلك"
        ];

        foreach ($govCities as $govName => $cityList) {

            // لو المحافظة مش موجودة -> ننشئها إذا كانت "غير ذلك" أو نتخطاها
            $governorate = Governorate::where('name', $govName)->first();
            if (!$governorate) {
                if ($govName === 'غير ذلك') {
                    $governorate = Governorate::create(['name' => 'غير ذلك']);
                } else {
                    continue;
                }
            }

            // إضافة "غير ذلك" للمدن لكل محافظة
            $cityList[] = 'غير ذلك';

            // علشان ميبقاش فيه تكرار — نعمل sync للمدن
            foreach ($cityList as $cityName) {
                City::updateOrCreate(
                    [
                        'governorate_id' => $governorate->id,
                        'name'           => $cityName,
                    ],
                    [] // مفيش تحديثات تانية
                );
            }

            // OPTIONAL: لو عاوز تحذف أي مدينة مش موجودة في السيدر
            // City::where('governorate_id', $governorate->id)
            //     ->whereNotIn('name', $cityList)
            //     ->delete();
        }
    }
}
