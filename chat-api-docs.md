# Chat API Documentation

## 📱 Tab 1: تحدث مع الدعم الفني

### الـ Flow:

```
1. فتح التاب → GET /api/chat/support (جلب الرسائل السابقة)
2. إرسال رسالة → POST /api/chat/support
```

### API Endpoints:

**🔵 جلب محادثة الدعم الفني:**
```http
GET /api/chat/support
Authorization: Bearer {token}
```

**Response:**
```json
{
    "meta": {
        "conversation_id": "uuid-here",
        "page": 1,
        "per_page": 50,
        "total": 10
    },
    "data": [
        {
            "id": 1,
            "conversation_id": "uuid",
            "sender": {
                "id": 5,
                "name": "اسم المستخدم",
                "is_support": false
            },
            "message": "مرحباً، عندي مشكلة",
            "read_at": null,
            "created_at": "2025-12-10T10:00:00Z"
        },
        {
            "id": 2,
            "sender": {
                "id": 1,
                "name": "فريق الدعم",
                "is_support": true
            },
            "message": "أهلاً بك، كيف نقدر نساعدك؟",
            "read_at": "2025-12-10T10:05:00Z",
            "created_at": "2025-12-10T10:02:00Z"
        }
    ]
}
```

**🔵 إرسال رسالة للدعم:**
```http
POST /api/chat/support
Authorization: Bearer {token}
Content-Type: application/json

{
    "message": "نص الرسالة هنا"
}
```

**Response:**
```json
{
    "message": "تم إرسال رسالتك للدعم الفني بنجاح",
    "data": {
        "id": 15,
        "conversation_id": "uuid",
        "message": "نص الرسالة",
        "created_at": "2025-12-10T10:30:00Z"
    }
}
```

---

## 📱 Tab 2: محادثات العملاء

### الـ Flow:

```
1. فتح التاب → GET /api/chat/inbox (قائمة المحادثات)
2. اختيار محادثة → GET /api/chat/{user_id} (جلب الرسائل)
3. إرسال رسالة → POST /api/chat/send
```

### API Endpoints:

**🔵 جلب قائمة المحادثات (Inbox):**
```http
GET /api/chat/inbox
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": [
        {
            "conversation_id": "uuid-1",
            "type": "peer",
            "last_message": "شكراً جزيلاً",
            "last_message_at": "2025-12-10T09:00:00Z",
            "is_read": false,
            "other_party": {
                "id": 10,
                "name": "أحمد محمد"
            },
            "unread_count": 3
        },
        {
            "conversation_id": "uuid-2",
            "type": "peer",
            "last_message": "تمام، متشكر",
            "last_message_at": "2025-12-09T15:00:00Z",
            "is_read": true,
            "other_party": {
                "id": 15,
                "name": "محمد علي"
            },
            "unread_count": 0
        }
    ],
    "unread_total": 3
}
```

**🔵 جلب محادثة مع مستخدم معين:**
```http
GET /api/chat/{user_id}
Authorization: Bearer {token}
```

**مثال:** `GET /api/chat/10` (محادثة مع المستخدم رقم 10)

**Response:**
```json
{
    "meta": {
        "conversation_id": "uuid-here",
        "page": 1,
        "per_page": 50,
        "total": 25
    },
    "data": [
        {
            "id": 1,
            "sender": { "id": 5, "name": "أنا" },
            "receiver": { "id": 10, "name": "أحمد محمد" },
            "message": "السلام عليكم",
            "read_at": "2025-12-10T08:00:00Z",
            "created_at": "2025-12-10T07:55:00Z"
        }
    ]
}
```

**🔵 إرسال رسالة لمستخدم:**
```http
POST /api/chat/send
**Headers:**
- `Authorization: Bearer <token>`
- `Content-Type: multipart/form-data` (في حالة إرسال ملف)
- `Accept: application/json`

**Body Parameters (FormData):**

| Parameter | Type | Required | Description |
| :--- | :--- | :--- | :--- |
| `receiver_id` | Integer | Yes | ID of the recipient user. |
| `message` | String | No* | Required if type is 'text' or 'listing_inquiry'. Optional if sending a file. |
| `file` | File | No* | Required if content_type is image/video/audio. |
| `listing_id` | Integer | No | ID of the listing (for listing inquiries). |
| `content_type` | String | No | `text`, `listing_inquiry`, `image`, `video`, `audio`, `file`. Default: `text`. |

**Example Request (Text):**
```json
{
    "receiver_id": 10,
    "message": "Hello!",
    "content_type": "text"
}
```

**Example Request (Image with Caption):**
```bash
receiver_id: 10
message: "صورة للمنتج"
content_type: "image"
file: (binary_image_data)
```

**Example Response:**
```json
{
    "message": "تم إرسال الرسالة بنجاح",
    "data": {
        "id": 125,
        "conversation_id": "peer:10:5",
        "message": "صورة للمنتج",
        "attachment": "https://domain.com/storage/chat/2025/12/images/xyz.jpg",
        "content_type": "image",
        "created_at": "2025-12-14T23:30:00.000000Z"
    }
}
```

---

## 🔔 إضافي: عدد الرسائل غير المقروءة

```http
GET /api/chat/unread-count
Authorization: Bearer {token}
```

**Response:**
```json
{
    "unread_count": 5
}
```

---

## 🏷️ بطاقة ملخص الإعلان (Listing Card)

تستخدم لعرض تفاصيل مختصرة عن الإعلان داخل المحادثة لتوضيح السياق.

**🔵 جلب ملخص الإعلان للمحادثة:**
```http
GET /api/chat/listing-summary/{category_slug}/{listing_id}
Authorization: Bearer {token}
```

**مثال:** `GET /api/chat/listing-summary/cars/456`

**Response:**
```json
{
    "success": true,
    "data": {
        "type": "listing_card",
        "listing_id": 456,
        "category_slug": "cars",
        "category_name": "سيارات للبيع",
        "title": "سيارة BMW 2020",
        "price": 550000.00,
        "currency": "ج.م",
        "price_formatted": "550,000 ج.م",
        "main_image_url": "https://example.com/storage/...",
        "governorate": "القاهرة",
        "city": "مدينة نصر",
        "status": "Valid",
        "published_at": "2025-12-14T10:00:00Z"
    }
}
```

---

## 📋 ملخص سريع

| الـ Tab | الوظيفة | الـ Endpoint |
|---------|---------|-------------|
| الدعم الفني | جلب المحادثة | `GET /api/chat/support` |
| الدعم الفني | إرسال رسالة | `POST /api/chat/support` |
| محادثات العملاء | قائمة المحادثات | `GET /api/chat/inbox` |
| محادثات العملاء | فتح محادثة | `GET /api/chat/{user_id}` |
| محادثات العملاء | إرسال رسالة | `POST /api/chat/send` |
| عام | عدد غير المقروء | `GET /api/chat/unread-count` |
| عام | ملخص الإعلان | `GET /api/chat/listing-summary/{slug}/{id}` |

> **ملاحظة:** كل الـ endpoints تحتاج `Authorization: Bearer {token}` في الـ Header.
