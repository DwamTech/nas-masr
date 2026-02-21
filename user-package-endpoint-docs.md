# User Package Endpoint Documentation

## 📦 الـ Endpoint الجديد

### GET /api/admin/users/{user_id}/package

**الوصف:** جلب معلومات الـ packages الخاصة بيوزر معين (للأدمن فقط)

---

## 🔐 Authentication

- **Required:** Yes
- **Type:** Bearer Token (Sanctum)
- **Role:** Admin only

---

## 📝 Request

### Headers
```http
Authorization: Bearer {admin_token}
Accept: application/json
```

### URL Parameters
- `user_id` (required, integer): الـ ID الخاص باليوزر

### Example Request
```bash
GET /api/admin/users/123/package
```

---

## ✅ Response (Success - 200)

### Example Response
```json
{
  "message": "Package retrieved successfully",
  "data": {
    "id": 5,
    "user_id": 123,
    "user_name": "أحمد محمد",
    "user_phone": "01234567890",
    
    "featured": {
      "ads_total": 10,
      "ads_used": 3,
      "ads_remaining": 7,
      "days": 30,
      "start_date": "2026-01-01T00:00:00.000000Z",
      "expire_date": "2026-01-31T00:00:00.000000Z",
      "active": true
    },
    
    "standard": {
      "ads_total": 20,
      "ads_used": 5,
      "ads_remaining": 15,
      "days": 30,
      "start_date": "2026-01-01T00:00:00.000000Z",
      "expire_date": "2026-01-31T00:00:00.000000Z",
      "active": true
    },
    
    "categories": [1, 2, 3],
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-04T19:55:00.000000Z"
  }
}
```

---

## ❌ Response (User Not Found - 404)

```json
{
  "message": "No records found."
}
```

---

## ❌ Response (No Package - 404)

```json
{
  "message": "No package found for this user",
  "data": null
}
```

---

## 🔍 Response Fields Description

### Main Data Object

| Field | Type | Description |
|-------|------|-------------|
| `id` | integer | معرف الباكدج |
| `user_id` | integer | معرف المستخدم |
| `user_name` | string | اسم المستخدم |
| `user_phone` | string | رقم هاتف المستخدم |
| `featured` | object | معلومات الباقة المميزة |
| `standard` | object | معلومات الباقة القياسية |
| `categories` | array | أرقام الفئات المسموح بها |
| `created_at` | datetime | تاريخ إنشاء الباكدج |
| `updated_at` | datetime | تاريخ آخر تحديث |

### Featured / Standard Object

| Field | Type | Description |
|-------|------|-------------|
| `ads_total` | integer | إجمالي الإعلانات المتاحة |
| `ads_used` | integer | عدد الإعلانات المستخدمة |
| `ads_remaining` | integer | عدد الإعلانات المتبقية |
| `days` | integer | عدد أيام صلاحية الباقة |
| `start_date` | datetime\|null | تاريخ بدء الباقة |
| `expire_date` | datetime\|null | تاريخ انتهاء الباقة |
| `active` | boolean | هل الباقة نشطة؟ |

---

## 📌 ملاحظات مهمة

1. **الباقة النشطة:**
   - الباقة تعتبر نشطة (`active: true`) إذا:
     - كان عندها رصيد إعلانات متبقي (`ads_remaining > 0`)
     - وتاريخ الانتهاء لم يمر بعد أو `null`

2. **الباقة غير محدودة:**
   - إذا كان `expire_date = null` والباقة عندها رصيد، معناه الباقة بدون مدة محددة

3. **لا توجد باقة:**
   - إذا اليوزر مش عنده أي package، هترجع 404 مع رسالة توضيحية

4. **Categories Array:**
   - Array فارغ `[]` معناه الباقة صالحة لكل الفئات
   - Array فيه أرقام معناه الباقة صالحة للفئات دي فقط

---

## 🧪 أمثلة استخدام

### cURL Example
```bash
curl -X GET \
  'http://your-domain.com/api/admin/users/123/package' \
  -H 'Authorization: Bearer YOUR_ADMIN_TOKEN' \
  -H 'Accept: application/json'
```

### JavaScript (Fetch) Example
```javascript
const userId = 123;
const token = 'YOUR_ADMIN_TOKEN';

fetch(`/api/admin/users/${userId}/package`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  console.log('Package Info:', data.data);
  console.log('Featured Remaining:', data.data.featured.ads_remaining);
  console.log('Standard Remaining:', data.data.standard.ads_remaining);
})
.catch(error => console.error('Error:', error));
```

### PHP (Laravel HTTP Client) Example
```php
use Illuminate\Support\Facades\Http;

$userId = 123;
$adminToken = 'YOUR_ADMIN_TOKEN';

$response = Http::withToken($adminToken)
    ->get("/api/admin/users/{$userId}/package");

if ($response->successful()) {
    $package = $response->json('data');
    $featuredRemaining = $package['featured']['ads_remaining'];
    $standardRemaining = $package['standard']['ads_remaining'];
    
    echo "Featured Ads Remaining: {$featuredRemaining}\n";
    echo "Standard Ads Remaining: {$standardRemaining}\n";
}
```

---

## 🔗 Related Endpoints

- `GET /api/admin/packages?user_id={id}` - جلب كل الباكدجات مع إمكانية الفلترة
- `POST /api/admin/user-packages` - إنشاء أو تحديث باكدج ليوزر
- `GET /api/my-packages` - جلب باكدجات اليوزر الحالي (للمستخدمين العاديين)

---

*آخر تحديث: 2026-01-04*
