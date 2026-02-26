# دليل النشر الآمن: ميزة is_representative

## المشكلة الأصلية
- المستخدم رقم 8 له `role = 'advertiser'` لكن عنده clients بيستخدموا referral_code = 8
- النظام الحالي يبحث عن `role = 'representative'` فقط
- المطلوب: السماح للمستخدم يكون معلن ومندوب في نفس الوقت

## الحل
إضافة حقل `is_representative` (boolean) منفصل عن `role`

### المميزات:
✅ المستخدم يقدر يكون `advertiser` و `representative` في نفس الوقت
✅ الـ `role` يفضل كما هو (لا تغيير في المنطق الحالي)
✅ آمن 100% على production
✅ لا يؤثر على الكود الموجود

## خطوات النشر

### 1. رفع الملفات المعدلة
```bash
# تأكد إنك في مجلد المشروع
cd /www/wwwroot/projects/nasmasr/back

# اسحب التحديثات من Git (أو ارفع الملفات يدوياً)
git pull origin main
```

### 2. تشغيل السكريبت الآمن
```bash
php safe_deploy_is_representative.php
```

هذا السكريبت سيقوم بـ:
- إضافة عمود `is_representative` للجدول
- تعيين `is_representative = true` لكل المناديب الحاليين
- إصلاح المستخدم رقم 8
- التحقق من نجاح العملية

### 3. مسح الـ Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 4. اختبار النظام
```bash
# اختبر الـ API
curl -X GET "https://back.nasmasr.app/api/debug/referral-codes"

# يجب أن ترى:
# - user_id_8: query_finds_it = true
# - user_id_30: query_finds_it = true
```

### 5. اختبار من التطبيق
- سجل دخول بحساب جديد
- حاول إضافة referral_code = 8
- يجب أن يعمل بنجاح ✅

## الملفات المعدلة

### Backend:
1. `database/migrations/2026_02_26_000001_add_is_representative_to_users.php` - Migration جديد
2. `app/Models/User.php` - إضافة `is_representative` للـ fillable و casts
3. `app/Http/Controllers/Api/UserController.php` - تعديل validation
4. `app/Http/Controllers/Api/AuthController.php` - تعديل validation
5. `app/Http/Resources/UserResource.php` - إضافة الحقل للـ API response

### Scripts:
- `safe_deploy_is_representative.php` - سكريبت النشر الآمن
- `debug_referral_code.php` - سكريبت التشخيص

## Rollback (في حالة الطوارئ)

إذا حدثت مشكلة، يمكن التراجع:

```bash
php artisan migrate:rollback --step=1
```

هذا سيحذف عمود `is_representative` ويعيد النظام كما كان.

## ملاحظات مهمة

### ✅ آمن:
- لا يغير الـ `role` الموجود
- لا يحذف أي بيانات
- يضيف عمود جديد فقط
- متوافق مع الكود الحالي

### ⚠️ بعد النشر:
- احذف route الـ debug من `routes/api.php` (السطر 54-106)
- احذف ملفات الـ debug: `debug_referral_code.php`, `fix_user_8_role.php`, إلخ

## الاستخدام المستقبلي

### لجعل مستخدم مندوب:
```php
$user->is_representative = true;
$user->save();
```

### للتحقق:
```php
if ($user->isRepresentative()) {
    // المستخدم مندوب
}
```

### في الـ Queries:
```php
// البحث عن المناديب
User::where('is_representative', true)->get();

// التحقق من referral code
$delegate = User::where('id', $code)
    ->where('is_representative', true)
    ->first();
```

## الدعم
إذا واجهت أي مشكلة، شغل:
```bash
php debug_referral_code.php
```
وابعت النتائج للمطور.
