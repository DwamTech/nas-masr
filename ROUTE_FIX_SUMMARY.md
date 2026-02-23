# إصلاح مشكلة الـ Routes (404 Error)

## 🐛 المشكلة

كانت الاختبارات تفشل بخطأ 404:
```
Expected response status code [201] but received 404.
```

## 🔍 السبب

الاختبارات كانت تستخدم route خاطئ:
```php
❌ الخطأ: /api/sections/{slug}/listings
✅ الصحيح: /api/v1/{slug}/listings
```

## ✅ الحل

تم تعديل جميع الـ routes في ملف الاختبار من:
```php
$this->postJson("/api/sections/{$this->category->slug}/listings", [...])
```

إلى:
```php
$this->postJson("/api/v1/{$this->category->slug}/listings", [...])
```

## 📝 التفاصيل التقنية

في ملف `routes/api.php`، الـ route معرّف كالتالي:

```php
Route::prefix('v1/{section}')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('listings', ListingController::class)
            ->only(['store', 'update', 'destroy', 'index', 'show']);
    });
});
```

هذا يعني أن الـ URL الصحيح هو:
- ✅ `/api/v1/{section}/listings` (POST لإنشاء إعلان)
- ❌ `/api/sections/{section}/listings` (غير موجود)

## 🚀 الآن جرب الاختبارات

```powershell
cd "E:\Work\Code\Dwam Projects\Nas Masr\nas-masr"
php artisan test --filter=ListingCreationWithFreePlanTest
```

أو:

```powershell
.\run_free_plan_tests.ps1
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
```

---

**تم الإصلاح:** 2026-02-23  
**الحالة:** ✅ جاهز للاختبار
