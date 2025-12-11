# توثيق API إدارة اشتراكات المستخدمين بالأقسام

## نظرة عامة

هذا الـ API يسمح للأدمن بإدارة اشتراكات المستخدمين في الأقسام المختلفة. يمكن للأدمن تحديد **عدد إعلانات مختلف لكل قسم على حدة** لكل مستخدم.

---

## الفرق بين الباقات والاشتراكات

| النوع | الوصف | التغطية |
|-------|-------|---------|
| **الباقات** (`packages`) | الأدمن يعطيها للمستخدم (تعاقد) | ✅ كل الأقسام |
| **الاشتراكات** (`subscriptions`) | اشتراك في قسم معين | ⚡ قسم واحد فقط |

---

## Endpoints

### 1. قائمة كل الاشتراكات

```http
GET /api/admin/user-subscriptions
Authorization: Bearer <admin_token>
```

**Query Parameters:**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `user_id` | integer | ❌ | فلتر بمستخدم معين |
| `category_id` | integer | ❌ | فلتر بقسم معين |
| `plan_type` | string | ❌ | `featured` أو `standard` |
| `active_only` | boolean | ❌ | عرض الاشتراكات النشطة فقط |
| `per_page` | integer | ❌ | عدد النتائج في الصفحة (default: 20) |

**Response:**

```json
{
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  },
  "items": [
    {
      "id": 1,
      "user_id": 123,
      "user_name": "أحمد محمد",
      "user_phone": "01012345678",
      "category_id": 2,
      "category_name": "السيارات",
      "category_slug": "cars",
      "plan_type": "featured",
      "days": 30,
      "subscribed_at": "2025-12-01T10:00:00Z",
      "expires_at": "2025-12-31T10:00:00Z",
      "ads_total": 20,
      "ads_used": 5,
      "remaining": 15,
      "price": 500.00,
      "payment_status": "admin_assigned",
      "active": true
    }
  ]
}
```

---

### 2. تفاصيل اشتراك

```http
GET /api/admin/user-subscriptions/{id}
Authorization: Bearer <admin_token>
```

**Response:**

```json
{
  "subscription": {
    "id": 1,
    "user_id": 123,
    "user_name": "أحمد محمد",
    "user_phone": "01012345678",
    "category_id": 2,
    "category_name": "السيارات",
    "category_slug": "cars",
    "plan_type": "featured",
    "days": 30,
    "subscribed_at": "2025-12-01T10:00:00Z",
    "expires_at": "2025-12-31T10:00:00Z",
    "ads_total": 20,
    "ads_used": 5,
    "remaining": 15,
    "price": 500.00,
    "ad_price": 25.00,
    "payment_status": "admin_assigned",
    "payment_method": "admin",
    "active": true,
    "created_at": "2025-12-01T10:00:00Z",
    "updated_at": "2025-12-10T15:30:00Z"
  }
}
```

---

### 3. إنشاء اشتراك جديد

```http
POST /api/admin/user-subscriptions
Authorization: Bearer <admin_token>
Content-Type: application/json
```

**Body:**

```json
{
  "user_id": 123,
  "category_slug": "cars",
  "plan_type": "featured",
  "ads_total": 20,
  "days": 30,
  "price": 500.00,
  "ad_price": 25.00,
  "start_now": true
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `user_id` | integer | ✅ | ID المستخدم |
| `category_id` | integer | ⚡ | ID القسم (مطلوب إذا لم يتم تحديد `category_slug`) |
| `category_slug` | string | ⚡ | slug القسم (مطلوب إذا لم يتم تحديد `category_id`) |
| `plan_type` | string | ✅ | `featured` أو `standard` |
| `ads_total` | integer | ✅ | **عدد الإعلانات المسموحة** |
| `days` | integer | ❌ | مدة الاشتراك بالأيام |
| `price` | number | ❌ | سعر الاشتراك |
| `ad_price` | number | ❌ | سعر الإعلان الواحد |
| `start_now` | boolean | ❌ | بدء الاشتراك فوراً (default: true) |

**Response:**

```json
{
  "success": true,
  "message": "تم إنشاء الاشتراك بنجاح ✅",
  "subscription": { ... }
}
```

---

### 4. تعديل اشتراك

```http
PATCH /api/admin/user-subscriptions/{id}
Authorization: Bearer <admin_token>
Content-Type: application/json
```

**Body:**

```json
{
  "ads_total": 30,
  "days": 45,
  "restart": true
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `ads_total` | integer | ❌ | **تعديل عدد الإعلانات الإجمالي** |
| `ads_used` | integer | ❌ | تعديل عدد الإعلانات المستخدمة |
| `days` | integer | ❌ | تعديل مدة الاشتراك |
| `expires_at` | date | ❌ | تعديل تاريخ الانتهاء |
| `restart` | boolean | ❌ | إعادة تشغيل الاشتراك من الآن |

**Response:**

```json
{
  "success": true,
  "message": "تم تحديث الاشتراك بنجاح ✅",
  "subscription": { ... }
}
```

---

### 5. إضافة إعلانات لاشتراك

```http
POST /api/admin/user-subscriptions/{id}/add-ads
Authorization: Bearer <admin_token>
Content-Type: application/json
```

**Body:**

```json
{
  "count": 10
}
```

**Response:**

```json
{
  "success": true,
  "message": "تم إضافة 10 إعلان للاشتراك ✅",
  "subscription": { ... }
}
```

---

### 6. حذف اشتراك

```http
DELETE /api/admin/user-subscriptions/{id}
Authorization: Bearer <admin_token>
```

**Response:**

```json
{
  "success": true,
  "message": "تم حذف الاشتراك بنجاح"
}
```

---

## مثال: إعداد اشتراكات لمستخدم واحد

```
المستخدم: أحمد (ID: 123)

┌─────────────┬──────────┬─────────────┬─────────┬─────────┐
│ القسم       │ الباقة   │ الإعلانات   │ المستخدم │ المتبقي │
├─────────────┼──────────┼─────────────┼─────────┼─────────┤
│ السيارات    │ featured │ 20          │ 5       │ 15      │
│ العقارات    │ standard │ 10          │ 3       │ 7       │
│ الوظائف     │ featured │ 5           │ 0       │ 5       │
└─────────────┴──────────┴─────────────┴─────────┴─────────┘
```

كل قسم له **عدد إعلانات مستقل**.

---

## ملاحظات

> [!IMPORTANT]
> - `ads_total` = إجمالي عدد الإعلانات المسموحة
> - `ads_used` = عدد الإعلانات المستخدمة
> - `remaining` = المتبقي (يُحسب تلقائياً)

> [!TIP]
> استخدم endpoint `/add-ads` لإضافة إعلانات بدون الحاجة لمعرفة العدد الحالي.
