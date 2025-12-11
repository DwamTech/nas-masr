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
Authorization: Bearer {token}
Content-Type: application/json

{
    "receiver_id": 10,
    "message": "نص الرسالة"
}
```

**Response:**
```json
{
    "message": "تم إرسال الرسالة بنجاح",
    "data": {
        "id": 50,
        "conversation_id": "uuid",
        "message": "نص الرسالة",
        "created_at": "2025-12-10T10:30:00Z"
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

## 📋 ملخص سريع

| الـ Tab | الوظيفة | الـ Endpoint |
|---------|---------|-------------|
| الدعم الفني | جلب المحادثة | `GET /api/chat/support` |
| الدعم الفني | إرسال رسالة | `POST /api/chat/support` |
| محادثات العملاء | قائمة المحادثات | `GET /api/chat/inbox` |
| محادثات العملاء | فتح محادثة | `GET /api/chat/{user_id}` |
| محادثات العملاء | إرسال رسالة | `POST /api/chat/send` |
| عام | عدد غير المقروء | `GET /api/chat/unread-count` |

> **ملاحظة:** كل الـ endpoints تحتاج `Authorization: Bearer {token}` في الـ Header.
