<?php

namespace App\Traits;

use App\Models\UserPackages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

trait PackageHelper
{
    public function consumeForPlan(int $userId, string $planType, int $count = 1): array
    {
        $planType = $this->normalizePlan($planType);

        $pkg = UserPackages::where('user_id', $userId)->first();

        if (!$pkg) {
            return $this->fail('لا توجد باقة لهذا المستخدم.');
        }

        if ($pkg->expire_date instanceof Carbon && $pkg->expire_date->isPast()) {
            return $this->fail('الباقة منتهية الصلاحية.');
        }

        [$totalField, $usedField, $title] = $this->mapFields($planType);

        $total  = (int) ($pkg->{$totalField} ?? 0);
        $used   = (int) ($pkg->{$usedField} ?? 0);
        $remain = max(0, $total - $used);

        if ($remain < $count) {
            return $this->fail("لا يوجد رصيد كافٍ في {$title} (المتبقي: {$remain}).");
        }

        // خصم ذري وآمن
        $pkg->increment($usedField, $count);

        return [
            'success'     => true,
            'message'     => "تم خصم {$count} إعلان من {$title} ✅",
            'plan'        => $planType,
            'total'       => $total,
            'used'        => $used + $count,
            'remaining'   => max(0, $total - ($used + $count)),
            'expire_date' => $pkg->expire_date,
            'package_id'  => $pkg->id,
        ];
    }


    protected function normalizePlan(string $plan): string
    {
        $plan = strtolower(trim($plan));
        return match ($plan) {
            'premium', 'featured' => 'featured',
            'standard'            => 'standard',
            // default               => $plan, // هيقع في الفيلدز الافتراضية لو غلط
        };
    }

    protected function mapFields(string $plan): array
    {
        return match ($plan) {
            'featured' => ['featured_ads', 'featured_ads_used', 'الباقة المتميزة'],
            'standard' => ['standard_ads', 'standard_ads_used', 'الباقة الاستاندرد'],
            // default    => ['standard_ads', 'standard_ads_used', 'الباقة الاستاندرد'],
        };
    }

    protected function fail(string $msg): array
    {
        return ['success' => false, 'message' => $msg];
    }
}
