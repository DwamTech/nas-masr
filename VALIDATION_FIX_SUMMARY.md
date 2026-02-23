# إصلاح مشكلة الـ Validation (422 Error)

## 🐛 المشكلة الثالثة

كانت الاختبارات تفشل بخطأ 422 (Validation Error):
```
Expected response status code [201] but received 422.
The following errors occurred during the last request:
{
  "message": "حقل المحافظة مطلوب. (and 5 more errors)",
  "errors": {
    "governorate": ["حقل المحافظة مطلوب."],
    "city": ["حقل المدينة مطلوب."],
    "lat": ["حقل خط العرض مطلوب."],
    "lng": ["حقل خط الطول مطلوب."],
    "address": ["حقل العنوان مطلوب."],
    "main_image": ["حقل الصورة الرئيسية مطلوب."]
  }
}
```

## 🔍 السبب

الاختبارات كانت ترسل بيانات ناقصة. الـ API يتطلب حقول إضافية:
- ❌ `governorate` (بالإضافة لـ governorate_id)
- ❌ `city` (بالإضافة لـ city_id)
- ❌ `lat` (خط العرض)
- ❌ `lng` (خط الطول)
- ❌ `address` (العنوان)
- ❌ `main_image` (الصورة الرئيسية)

## ✅ الحل

تم إنشاء helper method لتوفير بيانات كاملة:

```php
protected function getListingData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Test Ad',
        'description' => 'Test Description',
        'price' => 1000,
        'plan_type' => 'standard',
        'governorate_id' => $this->governorate->id,
        'governorate' => $this->governorate->id,      // ✅ مطلوب
        'city_id' => $this->city->id,
        'city' => $this->city->id,                    // ✅ مطلوب
        'lat' => 30.0444,                             // ✅ مطلوب
        'lng' => 31.2357,                             // ✅ مطلوب
        'address' => 'Test Address, Cairo',           // ✅ مطلوب
        'main_image' => 'test-image.jpg',             // ✅ مطلوب
    ], $overrides);
}
```

## 📝 الاستخدام

الآن يمكن استخدام الـ helper بسهولة:

```php
// بيانات افتراضية
$response = $this->postJson("/api/v1/{$slug}/listings", 
    $this->getListingData()
);

// تخصيص بعض الحقول
$response = $this->postJson("/api/v1/{$slug}/listings", 
    $this->getListingData([
        'title' => 'Custom Title',
        'plan_type' => 'featured',
        'price' => 5000,
    ])
);
```

## 🎯 الفوائد

1. ✅ كود أنظف وأقل تكراراً
2. ✅ سهولة التعديل في مكان واحد
3. ✅ ضمان إرسال جميع الحقول المطلوبة
4. ✅ مرونة في تخصيص البيانات لكل اختبار

## 🚀 الآن جرب الاختبارات

```powershell
cd "E:\Work\Code\Dwam Projects\Nas Masr\nas-masr"
.\run_free_plan_tests.ps1
```

أو:

```powershell
php artisan test --filter=ListingCreationWithFreePlanTest
```

## ✅ النتيجة المتوقعة

```
PASS  Tests\Feature\ListingCreationWithFreePlanTest
✓ ad accepted when plan price is zero
✓ featured ad accepted when price is zero
✓ payment required when price not zero and no package
✓ ad accepted when user has package balance
✓ payment required when package balance is zero
✓ admin can create ad without restrictions

Tests:  6 passed
Duration: X.XXs
```

---

## 📊 ملخص جميع الإصلاحات

| # | المشكلة | الحل | الحالة |
|---|---------|------|--------|
| 1 | حقول قاعدة البيانات غير موجودة | إزالة phone_verified_at و name_ar | ✅ |
| 2 | Route خاطئ (404) | تغيير من /api/sections إلى /api/v1 | ✅ |
| 3 | Validation errors (422) | إضافة جميع الحقول المطلوبة | ✅ |

---

**تم الإصلاح:** 2026-02-23  
**الحالة:** ✅ جاهز للاختبار النهائي
