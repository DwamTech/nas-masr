# Governorates & Cities API Documentation

## 📋 ملخص الـ Endpoints المتاحة

### ✅ Endpoints الموجودة والجاهزة:

| Endpoint | Method | وصف | Status |
|----------|--------|-----|--------|
| `/api/governorates` | GET | جلب كل المحافظات **مع المدن** | ✅ موجود |
| `/api/governorates/{id}` | GET | جلب محافظة معينة مع مدنها | ✅ موجود |
| `/api/governorates/{id}/cities` | GET | جلب مدن محافظة معينة | ✅ موجود |
| `/api/admin/cities/mappings` | GET | جلب mapping المدن مع IDs | ⭐ **جديد** |
| `/api/admin/governorates` | POST | إنشاء محافظة جديدة | ✅ موجود |
| `/api/admin/governorates/{id}` | PUT | تحديث محافظة | ✅ موجود |
| `/api/admin/governorates/{id}` | DELETE | حذف محافظة | ✅ موجود |
| `/api/admin/city/{governorate}` | POST | إضافة مدينة لمحافظة | ✅ موجود |
| `/api/admin/cities/{id}` | PUT | تحديث مدينة | ✅ موجود |
| `/api/admin/cities/{id}` | DELETE | حذف مدينة | ✅ موجود |

---

## 📍 تفاصيل الـ Endpoints

### 1️⃣ GET /api/governorates
**الوصف:** جلب جميع المحافظات مع المدن التابعة لها  
**Authentication:** Not required (Public)

#### Response:
```json
[
  {
    "id": 1,
    "name": "القاهرة",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z",
    "cities": [
      {
        "id": 101,
        "name": "مدينة نصر",
        "governorate_id": 1,
        "created_at": "2026-01-01T00:00:00.000000Z",
        "updated_at": "2026-01-01T00:00:00.000000Z"
      },
      {
        "id": 102,
        "name": "المعادي",
        "governorate_id": 1,
        "created_at": "2026-01-01T00:00:00.000000Z",
        "updated_at": "2026-01-01T00:00:00.000000Z"
      }
    ]
  },
  {
    "id": 2,
    "name": "الجيزة",
    "cities": [
      {
        "id": 201,
        "name": "الهرم",
        "governorate_id": 2
      }
    ]
  },
  {
    "id": null,
    "name": "غير ذلك",
    "cities": []
  }
]
```

#### ملاحظات:
- ✅ الـ endpoint يرجع المدن مع كل محافظة في array `cities`
- ✅ البيانات مرتبة alphabetically حسب اسم المحافظة
- ✅ يضيف "غير ذلك" كخيار إضافي في النهاية

---

### 2️⃣ GET /api/governorates/{id}
**الوصف:** جلب محافظة معينة مع مدنها (عبر الـ relationship الموجود)  
**Authentication:** Not required (Public)

#### Request:
```
GET /api/governorates/1
```

#### Response:
```json
{
  "id": 1,
  "name": "القاهرة",
  "created_at": "2026-01-01T00:00:00.000000Z",
  "updated_at": "2026-01-01T00:00:00.000000Z",
  "cities": [
    {
      "id": 101,
      "name": "مدينة نصر",
      "governorate_id": 1
    },
    {
      "id": 102,
      "name": "المعادي",
      "governorate_id": 1
    }
  ]
}
```

---

### 3️⃣ GET /api/governorates/{id}/cities
**الوصف:** جلب مدن محافظة معينة فقط  
**Authentication:** Not required (Public)

#### Request:
```
GET /api/governorates/1/cities
```

#### Response:
```json
[
  {
    "id": 101,
    "name": "مدينة نصر",
    "governorate_id": 1,
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z"
  },
  {
    "id": 102,
    "name": "المعادي",
    "governorate_id": 1
  },
  {
    "id": null,
    "name": "غير ذلك",
    "governorate_id": 1
  }
]
```

#### ملاحظات:
- يضيف "غير ذلك" كخيار إضافي في النهاية

---

### 4️⃣ ⭐ GET /api/admin/cities/mappings (جديد!)
**الوصف:** جلب mapping للمدن مع IDs منظم حسب المحافظة (لتسهيل البحث والعرض)  
**Authentication:** Required (Admin only)

#### Headers:
```http
Authorization: Bearer {admin_token}
Accept: application/json
```

#### Response:
```json
{
  "success": true,
  "data": {
    "by_governorate_id": {
      "1": {
        "مدينة نصر": 101,
        "المعادي": 102,
        "الزمالك": 103,
        "غير ذلك": 104
      },
      "2": {
        "الهرم": 201,
        "الدقي": 202,
        "المهندسين": 203,
        "غير ذلك": 204
      }
    },
    "by_governorate_name": {
      "القاهرة": {
        "مدينة نصر": 101,
        "المعادي": 102,
        "الزمالك": 103,
        "غير ذلك": 104
      },
      "الجيزة": {
        "الهرم": 201,
        "الدقي": 202,
        "المهندسين": 203,
        "غير ذلك": 204
      }
    }
  }
}
```

#### Use Cases:
1. **البحث السريع عن city_id:**
   ```javascript
   const cityId = data.by_governorate_id["1"]["مدينة نصر"]; // 101
   ```

2. **البحث باسم المحافظة:**
   ```javascript
   const cityId = data.by_governorate_name["القاهرة"]["المعادي"]; // 102
   ```

3. **استبدال localStorage:**
   - يمكن استخدام هذا الـ endpoint بديلاً عن تخزين البيانات في localStorage
   - يحل مشاكل التوافق على الموبايل

---

## 🔧 Admin Endpoints

### 5️⃣ POST /api/admin/governorates
**الوصف:** إنشاء محافظة جديدة  
**Authentication:** Required (Admin)

#### Request Body:
```json
{
  "name": "أسوان"
}
```

#### Response:
```json
{
  "id": 3,
  "name": "أسوان",
  "created_at": "2026-01-04T20:30:00.000000Z",
  "updated_at": "2026-01-04T20:30:00.000000Z",
  "cities": [
    {
      "id": 301,
      "name": "غير ذلك",
      "governorate_id": 3
    }
  ]
}
```

#### ملاحظات:
- ✅ يتم إنشاء "غير ذلك" تلقائياً كأول مدينة للمحافظة الجديدة

---

### 6️⃣ PUT /api/admin/governorates/{id}
**الوصف:** تحديث اسم محافظة  
**Authentication:** Required (Admin)

#### Request Body:
```json
{
  "name": "القاهرة الكبرى"
}
```

---

### 7️⃣ DELETE /api/admin/governorates/{id}
**الوصف:** حذف محافظة  
**Authentication:** Required (Admin)

#### Response (Success):
```json
{
  "success": true,
  "message": "تم حذف المحافظة بنجاح"
}
```

#### Response (Error - محافظة مستخدمة):
```json
{
  "success": false,
  "message": "لا يمكن حذف المحافظة لأنها مستخدمة في الإعلانات."
}
```

---

### 8️⃣ POST /api/admin/city/{governorate_id}
**الوصف:** إضافة مدينة جديدة لمحافظة  
**Authentication:** Required (Admin)

#### Request:
```
POST /api/admin/city/1
```

#### Request Body:
```json
{
  "name": "التجمع الخامس"
}
```

#### Response:
```json
{
  "id": 105,
  "name": "التجمع الخامس",
  "governorate_id": 1,
  "created_at": "2026-01-04T20:35:00.000000Z",
  "updated_at": "2026-01-04T20:35:00.000000Z"
}
```

---

### 9️⃣ PUT /api/admin/cities/{id}
**الوصف:** تحديث بيانات مدينة  
**Authentication:** Required (Admin)

#### Request Body:
```json
{
  "name": "مدينة نصر الجديدة",
  "governorate_id": 1
}
```

---

### 🔟 DELETE /api/admin/cities/{id}
**الوصف:** حذف مدينة  
**Authentication:** Required (Admin)

#### Response (Success):
```json
{
  "success": true,
  "message": "تم حذف المدينة بنجاح."
}
```

#### Response (Error - مدينة مستخدمة):
```json
{
  "success": false,
  "message": "لا يمكن حذف المدينة لأنها مستخدمة في الإعلانات."
}
```

---

## 💡 Examples

### JavaScript Example - Using the new mappings endpoint:

```javascript
// Fetch cities mappings once
const response = await fetch('/api/admin/cities/mappings', {
  headers: {
    'Authorization': `Bearer ${adminToken}`,
    'Accept': 'application/json'
  }
});

const { data } = await response.json();

// Now you can easily lookup city IDs:
const getCityId = (governorateName, cityName) => {
  return data.by_governorate_name[governorateName]?.[cityName] || null;
};

// Example usage:
const cityId = getCityId("القاهرة", "مدينة نصر"); // Returns 101
console.log(`City ID for 'مدينة نصر' in 'القاهرة': ${cityId}`);

// Or by governorate ID:
const cityIdById = data.by_governorate_id["1"]["المعادي"]; // Returns 102
```

### PHP Example - Fetching governorates with cities:

```php
use Illuminate\Support\Facades\Http;

$response = Http::get('/api/governorates');
$governorates = $response->json();

foreach ($governorates as $gov) {
    echo "محافظة: {$gov['name']}\n";
    echo "المدن:\n";
    
    foreach ($gov['cities'] as $city) {
        echo "  - {$city['name']} (ID: {$city['id']})\n";
    }
    echo "\n";
}
```

---

## 📊 Database Structure

### الجدول `governorates`:
```sql
- id (bigint, primary key)
- name (string, unique)
- created_at (timestamp)
- updated_at (timestamp)
```

### الجدول `cities`:
```sql
- id (bigint, primary key)
- name (string)
- governorate_id (bigint, foreign key → governorates.id)
- created_at (timestamp)
- updated_at (timestamp)
```

---

## ✅ خلاصة التغييرات

### ما كان موجود:
- ✅ `GET /api/governorates` - **يرجع المدن مع كل محافظة**
- ✅ `GET /api/governorates/{id}/cities`
- ✅ كل الـ CRUD operations للمحافظات والمدن

### ما تم إضافته:
- ⭐ `GET /api/admin/cities/mappings` - **endpoint جديد** لتسهيل الوصول للـ city IDs

### فوائد الـ endpoint الجديد:
1. **بديل localStorage:** لا حاجة لتخزين البيانات محلياً
2. **سرعة البحث:** O(1) lookup بدلاً من loop
3. **توافق الموبايل:** يحل مشاكل localStorage على الأجهزة المحمولة
4. **مرونة:** يوفر طريقتين للبحث (بـ ID أو بالاسم)

---

*آخر تحديث: 2026-01-04*
