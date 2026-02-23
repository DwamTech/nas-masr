# دليل الاختبار السريع - Free Plan Logic

## اختبار سريع للتأكد من عمل المنطق الجديد

### الخطوة 1: تشغيل الاختبارات الآلية

```bash
# على Windows
run_free_plan_tests.bat

# على Linux/Mac
chmod +x run_free_plan_tests.sh
./run_free_plan_tests.sh
```

### الخطوة 2: اختبار يدوي باستخدام Postman/API

#### السيناريو 1: سعر الباقة = 0 (الحالة الجديدة)

**1. إعداد القسم:**
```sql
-- تأكد من وجود قسم بسعر = 0
UPDATE category_plan_prices 
SET standard_ad_price = 0, 
    standard_days = 15 
WHERE category_id = 1;
```

**2. إنشاء إعلان:**
```http
POST /api/sections/{category_slug}/listings
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Test Ad - Free Plan",
  "description": "Testing free plan logic",
  "price": 1000,
  "plan_type": "standard",
  "governorate_id": 1,
  "city_id": 1
}
```

**3. النتيجة المتوقعة:**
```json
{
  "data": {
    "id": 123,
    "title": "Test Ad - Free Plan",
    "status": "Valid",
    "publish_via": "free_plan"
  },
  "payment": {
    "type": "free_plan",
    "plan_type": "standard",
    "price": 0
  }
}
```

✅ **إذا حصلت على هذه النتيجة، المنطق يعمل بشكل صحيح!**

---

#### السيناريو 2: سعر الباقة > 0 بدون رصيد

**1. إعداد القسم:**
```sql
UPDATE category_plan_prices 
SET standard_ad_price = 50 
WHERE category_id = 1;
```

**2. إنشاء إعلان:**
```http
POST /api/sections/{category_slug}/listings
(نفس البيانات السابقة)
```

**3. النتيجة المتوقعة:**
```json
{
  "success": false,
  "message": "لا تملك باقة فعّالة أو رصيد كافٍ، يجب عليك الدفع لنشر هذا الإعلان.",
  "payment_required": true,
  "listing_id": 124
}
```

✅ **Status Code: 402 Payment Required**

---

#### السيناريو 3: سعر الباقة > 0 مع رصيد

**1. إضافة باقة للمستخدم:**
```sql
INSERT INTO user_packages (user_id, standard_ads, standard_ads_used, standard_days, categories)
VALUES (1, 5, 0, 15, '[1]');
```

**2. إنشاء إعلان:**
```http
POST /api/sections/{category_slug}/listings
(نفس البيانات السابقة)
```

**3. النتيجة المتوقعة:**
```json
{
  "data": {
    "id": 125,
    "status": "Valid",
    "publish_via": "package"
  },
  "payment": {
    "type": "package",
    "price": 50
  }
}
```

**4. التحقق من خصم الرصيد:**
```sql
SELECT standard_ads_used FROM user_packages WHERE user_id = 1;
-- يجب أن يكون = 1
```

✅ **تم خصم إعلان واحد من الرصيد**

---

## جدول مقارنة النتائج

| السيناريو | سعر الباقة | رصيد المستخدم | النتيجة المتوقعة | نوع الدفع |
|-----------|------------|---------------|------------------|-----------|
| 1 | 0 | لا يوجد | ✅ قبول | free_plan |
| 2 | 50 | لا يوجد | ❌ رفض | payment_required |
| 3 | 50 | 5 إعلانات | ✅ قبول | package |
| 4 | 50 | 0 إعلانات | ❌ رفض | payment_required |
| 5 (أدمن) | 100 | لا يوجد | ✅ قبول | admin_bypass |

---

## التحقق من قاعدة البيانات

### التحقق من الإعلان المنشور:
```sql
SELECT 
    id, 
    title, 
    status, 
    publish_via, 
    plan_type,
    expire_at,
    user_id
FROM listings 
WHERE title LIKE '%Test Ad%'
ORDER BY id DESC 
LIMIT 5;
```

### التحقق من رصيد الباقات:
```sql
SELECT 
    user_id,
    standard_ads,
    standard_ads_used,
    (standard_ads - standard_ads_used) as remaining,
    standard_expire_date
FROM user_packages
WHERE user_id = 1;
```

### التحقق من أسعار الأقسام:
```sql
SELECT 
    c.name,
    c.slug,
    cpp.standard_ad_price,
    cpp.featured_ad_price,
    cpp.standard_days,
    cpp.featured_days
FROM categories c
LEFT JOIN category_plan_prices cpp ON c.id = cpp.category_id
WHERE c.id = 1;
```

---

## استكشاف الأخطاء الشائعة

### ❌ الإعلان مرفوض رغم أن السعر = 0

**السبب المحتمل:**
- لم يتم حفظ التعديل في قاعدة البيانات
- الـ cache لم يتم تحديثه

**الحل:**
```bash
php artisan cache:clear
php artisan config:clear
```

### ❌ الإعلان مقبول رغم عدم وجود رصيد

**السبب المحتمل:**
- سعر الباقة = 0 في قاعدة البيانات

**التحقق:**
```sql
SELECT standard_ad_price FROM category_plan_prices WHERE category_id = 1;
```

### ❌ خطأ في الـ API

**الحل:**
```bash
# تحقق من الـ logs
tail -f storage/logs/laravel.log

# تحقق من الـ database connection
php artisan migrate:status
```

---

## ملخص سريع

✅ **المنطق الجديد يعمل إذا:**
1. الإعلان يُقبل عندما سعر الباقة = 0 بدون أي شروط
2. الإعلان يُرفض عندما سعر الباقة > 0 وبدون رصيد
3. الإعلان يُقبل عندما يوجد رصيد ويتم خصمه
4. الأدمن يمكنه إنشاء إعلانات دائماً

---

## الخطوة التالية

بعد التأكد من نجاح الاختبارات:
1. ✅ اختبر على بيئة staging
2. ✅ راجع الكود مع الفريق
3. ✅ حدّث الـ documentation
4. ✅ انشر على production

---

**تم إنشاء هذا الدليل بواسطة:** Kiro AI Assistant
**التاريخ:** 2026-02-23
