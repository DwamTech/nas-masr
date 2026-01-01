# نظام المناديب (Representatives System)

## نظرة عامة
نظام المناديب يسمح للمستخدمين بأن يصبحوا مناديب ويجلبوا عملاء جدد للمنصة.

---

## بنية البيانات

### جدول `users`
كل مستخدم لديه:
- `id` - معرف المستخدم (تسلسلي)
- `referral_code` - كود المندوب الذي جلب هذا المستخدم (اختياري، nullable)
- `role` - دور المستخدم: `user`, `advertiser`, `representative`, `admin`

### جدول `user_clients`
يخزن العلاقة بين المندوب وعملائه:
- `id` - معرف السجل (Auto-increment)
- `user_id` - معرف المستخدم الذي أصبح مندوب (unique)
- `clients` - JSON Array يحتوي على IDs العملاء المرتبطين بالمندوب

---

## كود المستخدم (User Code)
**كود المستخدم = User ID**

مثال:
- المستخدم #25 → كود المستخدم = `"25"`
- المستخدم #100 → كود المستخدم = `"100"`

---

## كود المندوب (Delegate Code)
**كود المندوب = User ID للمندوب**

عندما يصبح المستخدم مندوب، كود المندوب الخاص به = User ID الخاص به.

مثال:
- المستخدم #25 أصبح مندوب → كود المندوب = `"25"`

---

## API Endpoints

### 1. طلب أن يصبح مندوب
```
POST /api/create-agent-code
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "You are now a representative. Your delegate code is: 25",
  "user_code": "25",
  "role": "representative",
  "data": {
    "id": 10,
    "user_id": 25,
    "clients": [],
    "created_at": "2026-01-01T10:00:00.000000Z",
    "updated_at": "2026-01-01T10:00:00.000000Z"
  }
}
```

**ملاحظة:**
- `user_code` و `role` - الـ structure الجديد (مستحسن)
- `data` - الـ structure القديم (backward compatibility)
- يمكنك استخدام أي منهما

**ماذا يحدث:**
1. تغيير `role` من `user` إلى `representative`
2. إنشاء سجل في جدول `user_clients` مع `clients` فارغة
3. إرجاع كود المندوب (= User ID)

---

### 2. التسجيل مع كود مندوب (اختياري)
```
POST /api/register
Content-Type: application/json

{
  "name": "أحمد محمد",
  "phone": "01234567890",
  "password": "123456",
  "referral_code": "25"  // كود المندوب (اختياري)
}
```

**ماذا يحدث:**
1. التحقق من أن `referral_code = 25` هو User ID لمستخدم دوره `representative`
2. إنشاء المستخدم الجديد وحفظ `referral_code = "25"` في جدول `users`
3. إضافة ID المستخدم الجديد إلى `user_clients.clients` للمندوب #25

---

### 3. تعديل الملف الشخصي وإضافة كود المندوب
```
PUT /api/edit-profile
Authorization: Bearer {token}
Content-Type: application/json

{
  "referral_code": "25"  // إضافة كود المندوب بعد التسجيل
}
```

**القيود:**
- **لا يمكن تغيير `referral_code` بعد إضافته مرة واحدة**
- يجب أن يكون الكود صحيح ويخص مندوب موجود

**ماذا يحدث:**
1. التحقق من صحة كود المندوب
2. التحقق من أن المستخدم لم يضف كود مندوب مسبقاً
3. حفظ `referral_code` وإضافة المستخدم إلى قائمة `clients` المندوب

---

### 4. جلب عملاء المندوب
```
GET /api/all-clients
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Clients retrieved successfully",
  "user_code": "25",
  "clients_count": 3,
  "data": [
    {
      "id": 30,
      "name": "أحمد محمد",
      "phone": "01234567890",
      "user_code": "30",
      "role": "user",
      "status": "active",
      "registered_at": "2026-01-01",
      "listings_count": 5
    },
    {
      "id": 45,
      "name": "محمد علي",
      "phone": "01098765432",
      "user_code": "45",
      "role": "advertiser",
      "status": "active",
      "registered_at": "2026-01-02",
      "listings_count": 12
    }
  ]
}
```

---

## سيناريوهات الاستخدام

### السيناريو 1: مستخدم يصبح مندوب
```
1. المستخدم #25 يطلب أن يصبح مندوب
   POST /api/create-agent-code
   
2. النظام يغير role إلى "representative"
3. النظام ينشئ سجل في user_clients:
   {
     "id": 10,
     "user_id": 25,
     "clients": []
   }
   
4. المستخدم يحصل على كود المندوب = "25"
```

### السيناريو 2: مستخدم جديد يسجل بكود مندوب
```
1. المستخدم الجديد يسجل مع referral_code = "25"
   POST /api/register
   {
     "name": "أحمد",
     "phone": "01234567890",
     "password": "123456",
     "referral_code": "25"
   }
   
2. النظام يتحقق من أن المستخدم #25 مندوب (role = "representative")
3. النظام ينشئ المستخدم الجديد (مثلاً ID = 67)
4. النظام يضيف 67 إلى clients المندوب:
   {
     "user_id": 25,
     "clients": [67]
   }
```

### السيناريو 3: مستخدم يضيف كود مندوب لاحقاً
```
1. المستخدم #67 سجل بدون referral_code
2. لاحقاً يريد إضافة المندوب:
   PUT /api/edit-profile
   {
     "referral_code": "25"
   }
   
3. النظام يتحقق ويضيف 67 إلى clients المندوب #25
4. لا يمكن للمستخدم #67 تغيير referral_code مرة أخرى
```

---

## قواعد مهمة

1. **كود المستخدم = User ID** (تسلسلي)
2. **كود المندوب = User ID للمندوب**
3. **لا يتم توليد أي أكواد عشوائية**
4. **referral_code لا يمكن تغييره بعد إضافته**
5. **فقط المستخدمين بـ role = "representative" يمكنهم أن يكون لهم عملاء**
6. **user_clients.user_id فريد (unique)**

---

## التحديثات في قاعدة البيانات

### Migration جديد
تم إضافة unique constraint على `user_clients.user_id`:
```
php artisan migrate
```

هذا يضمن أن كل مندوب له سجل واحد فقط في `user_clients`.

---

## أمثلة على البيانات

### جدول users
```
| id  | name         | phone        | role           | referral_code |
|-----|--------------|--------------|----------------|---------------|
| 25  | محمد أحمد    | 01111111111  | representative | null          |
| 30  | أحمد محمد    | 01234567890  | user           | 25            |
| 45  | علي حسن      | 01098765432  | advertiser     | 25            |
| 67  | حسن علي      | 01122334455  | user           | 25            |
```

### جدول user_clients
```
| id | user_id | clients           |
|----|---------|-------------------|
| 10 | 25      | [30, 45, 67]      |
```

**في هذا المثال:**
- المندوب #25 لديه 3 عملاء: 30, 45, 67
- كل العملاء لديهم `referral_code = "25"`
- كود المندوب = "25" (User ID)
