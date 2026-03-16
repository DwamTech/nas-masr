# تعليمات تشغيل الاختبارات

## المشكلة التي تم حلها

كانت الاختبارات تفشل بسبب:
1. ❌ استخدام حقل `phone_verified_at` غير موجود في جدول users
2. ❌ استخدام IDs ثابتة (1) للـ governorate و city

## الحل المطبق

1. ✅ إزالة `phone_verified_at` من إنشاء المستخدمين
2. ✅ إنشاء governorate و city ديناميكياً واستخدام IDs الحقيقية
3. ✅ تبسيط البيانات المطلوبة

## كيفية تشغيل الاختبارات

### على PowerShell (Windows):

```powershell
# الانتقال لمجلد المشروع
cd "E:\Work\Code\Dwam Projects\Nas Masr\nas-masr"

# الطريقة 1: تشغيل مباشر
php artisan test --filter=ListingCreationWithFreePlanTest

# الطريقة 2: استخدام PowerShell script
.\run_free_plan_tests.ps1

# الطريقة 3: استخدام Batch file
.\run_free_plan_tests.bat
```

### على CMD (Windows):

```cmd
cd E:\Work\Code\Dwam Projects\Nas Masr\nas-masr
php artisan test --filter=ListingCreationWithFreePlanTest
```

### على Linux/Mac:

```bash
cd /path/to/nas-masr
php artisan test --filter=ListingCreationWithFreePlanTest

# أو استخدام shell script
chmod +x run_free_plan_tests.sh
./run_free_plan_tests.sh
```

## تشغيل اختبار واحد فقط

```bash
# اختبار السعر = 0
php artisan test --filter=test_ad_accepted_when_plan_price_is_zero

# اختبار الأدمن
php artisan test --filter=test_admin_can_create_ad_without_restrictions
```

## النتيجة المتوقعة

```
PASS  Tests\Feature\ListingCreationWithFreePlanTest
✓ ad accepted when plan price is zero
✓ featured ad accepted when price is zero
✓ payment required when price not zero and no package
✓ ad accepted when user has package balance
✓ payment required when package balance is zero
✓ admin can create ad without restrictions

Tests:  6 passed
Time:   X.XXs
```

## استكشاف الأخطاء

### خطأ: "Class 'Tests\Feature\Governorate' not found"

**الحل:** تأكد من إضافة use statement في أعلى الملف:
```php
use App\Models\Governorate;
use App\Models\City;
```

### خطأ: "SQLSTATE[HY000]: General error: 1 no such table"

**الحل:** قم بتشغيل migrations للاختبارات:
```bash
php artisan migrate --env=testing
```

أو تأكد من وجود ملف `phpunit.xml` بالإعدادات الصحيحة:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### خطأ: "Target class [ListingController] does not exist"

**الحل:** تأكد من تثبيت dependencies:
```bash
composer install
```

### خطأ: "Route [sections.listings.store] not defined"

**الحل:** تأكد من أن routes محملة بشكل صحيح:
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

## ملاحظات مهمة

1. **RefreshDatabase:** الاختبارات تستخدم `RefreshDatabase` trait، مما يعني أن قاعدة البيانات يتم إعادة بنائها لكل اختبار

2. **Isolation:** كل اختبار مستقل تماماً ولا يؤثر على الآخر

3. **In-Memory Database:** الاختبارات تستخدم SQLite في الذاكرة للسرعة

4. **Sanctum:** الاختبارات تستخدم Laravel Sanctum للمصادقة

## التحقق من نجاح التعديل

بعد تشغيل الاختبارات بنجاح، يمكنك التحقق من أن المنطق الجديد يعمل:

1. ✅ الإعلانات تُقبل عندما سعر الباقة = 0
2. ✅ الإعلانات تُرفض عندما سعر الباقة > 0 وبدون رصيد
3. ✅ الإعلانات تُقبل عندما يوجد رصيد ويتم خصمه
4. ✅ الأدمن يمكنه إنشاء إعلانات دائماً

## الخطوة التالية

بعد نجاح الاختبارات:
1. اختبر على بيئة staging
2. راجع الكود مع الفريق
3. انشر على production

---

**آخر تحديث:** 2026-02-23
