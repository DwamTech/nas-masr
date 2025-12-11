# Dashboard Chat API Documentation

## 🖥️ السيناريو الأول: مراقبة محادثات العملاء مع بعض

> **ملاحظة:** هذه الصفحة للمراقبة فقط (Read-Only) - لا يمكن للأدمن التدخل في المحادثات.

### الـ Flow:

```
1. فتح الصفحة → GET /api/admin/monitoring/conversations (قائمة المحادثات)
2. اختيار محادثة → GET /api/admin/monitoring/conversations/{conversationId} (عرض الرسائل)
3. (اختياري) بحث → GET /api/admin/monitoring/search?q=اسم
4. (اختياري) إحصائيات → GET /api/admin/monitoring/stats
```

### API Endpoints:

**🔵 جلب قائمة المحادثات بين العملاء:**
```http
GET /api/admin/monitoring/conversations
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `per_page` (optional): عدد النتائج في الصفحة (default: 20)

**Response:**
```json
{
    "meta": {
        "page": 1,
        "per_page": 20,
        "total": 150,
        "last_page": 8
    },
    "data": [
        {
            "conversation_id": "uuid-1",
            "participants": [
                { "id": 5, "name": "أحمد محمد", "phone": "01000000000" },
                { "id": 10, "name": "محمد علي", "phone": "01111111111" }
            ],
            "started_at": "2025-12-01T10:00:00Z",
            "last_message_at": "2025-12-10T09:30:00Z",
            "messages_count": 45
        },
        {
            "conversation_id": "uuid-2",
            "participants": [
                { "id": 3, "name": "سارة أحمد", "phone": "01222222222" },
                { "id": 8, "name": "نور محمد", "phone": "01333333333" }
            ],
            "started_at": "2025-12-05T14:00:00Z",
            "last_message_at": "2025-12-09T18:00:00Z",
            "messages_count": 22
        }
    ]
}
```

---

**🔵 عرض محادثة معينة:**
```http
GET /api/admin/monitoring/conversations/{conversationId}
Authorization: Bearer {admin_token}
```

**مثال:** `GET /api/admin/monitoring/conversations/uuid-1`

**Response:**
```json
{
    "meta": {
        "conversation_id": "uuid-1",
        "participants": [
            { "id": 5, "name": "أحمد محمد", "phone": "01000000000" },
            { "id": 10, "name": "محمد علي", "phone": "01111111111" }
        ],
        "page": 1,
        "per_page": 50,
        "total": 45
    },
    "data": [
        {
            "id": 1,
            "sender": { "id": 5, "name": "أحمد محمد" },
            "receiver": { "id": 10, "name": "محمد علي" },
            "message": "السلام عليكم",
            "read_at": "2025-12-01T10:05:00Z",
            "created_at": "2025-12-01T10:00:00Z"
        },
        {
            "id": 2,
            "sender": { "id": 10, "name": "محمد علي" },
            "receiver": { "id": 5, "name": "أحمد محمد" },
            "message": "وعليكم السلام",
            "read_at": "2025-12-01T10:10:00Z",
            "created_at": "2025-12-01T10:05:00Z"
        }
    ]
}
```

---

**🔵 البحث عن محادثات:**
```http
GET /api/admin/monitoring/search?q={search_term}
Authorization: Bearer {admin_token}
```

**مثال:** `GET /api/admin/monitoring/search?q=أحمد`

**Response:**
```json
{
    "users_found": 3,
    "conversations_found": 5,
    "data": [
        {
            "conversation_id": "uuid-1",
            "participants": [
                { "id": 5, "name": "أحمد محمد", "phone": "01000000000" },
                { "id": 10, "name": "محمد علي", "phone": "01111111111" }
            ],
            "last_message_at": "2025-12-10T09:30:00Z",
            "messages_count": 45
        }
    ]
}
```

---

**🔵 إحصائيات المحادثات:**
```http
GET /api/admin/monitoring/stats
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "total_peer_conversations": 150,
    "total_peer_messages": 3500,
    "today_messages": 45,
    "active_users_today": 28
}
```

---

## 🖥️ السيناريو الثاني: محادثات الدعم الفني

### الـ Flow:

```
1. فتح الصفحة → GET /api/admin/support/inbox (قائمة طلبات الدعم)
2. اختيار محادثة → GET /api/admin/support/{user_id} (عرض المحادثة)
3. الرد على العميل → POST /api/admin/support/reply
4. (اختياري) تحديد كمقروء → PATCH /api/admin/support/{user_id}/read
5. (اختياري) إحصائيات → GET /api/admin/support/stats
```

### API Endpoints:

**🔵 جلب قائمة محادثات الدعم (Unified Inbox):**
```http
GET /api/admin/support/inbox
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `per_page` (optional): عدد النتائج في الصفحة (default: 20)

**Response:**
```json
{
    "meta": {
        "page": 1,
        "per_page": 20,
        "total": 35,
        "last_page": 2
    },
    "data": [
        {
            "conversation_id": "support-uuid-1",
            "user": {
                "id": 5,
                "name": "أحمد محمد",
                "phone": "01000000000",
                "email": "ahmed@example.com"
            },
            "last_message": "شكراً على المساعدة",
            "last_message_at": "2025-12-10T09:30:00Z",
            "last_message_by": "أحمد محمد",
            "messages_count": 8,
            "unread_count": 2
        },
        {
            "conversation_id": "support-uuid-2",
            "user": {
                "id": 10,
                "name": "سارة علي",
                "phone": "01111111111",
                "email": "sara@example.com"
            },
            "last_message": "تم حل المشكلة",
            "last_message_at": "2025-12-09T15:00:00Z",
            "last_message_by": "فريق الدعم",
            "messages_count": 5,
            "unread_count": 0
        }
    ]
}
```

---

**🔵 عرض محادثة دعم مع مستخدم معين:**
```http
GET /api/admin/support/{user_id}
Authorization: Bearer {admin_token}
```

**مثال:** `GET /api/admin/support/5`

**Response:**
```json
{
    "meta": {
        "conversation_id": "support-uuid-1",
        "user": {
            "id": 5,
            "name": "أحمد محمد",
            "phone": "01000000000",
            "email": "ahmed@example.com"
        },
        "page": 1,
        "per_page": 50,
        "total": 8
    },
    "data": [
        {
            "id": 1,
            "sender_id": 5,
            "sender_type": "App\\Models\\User",
            "message": "السلام عليكم، عندي مشكلة في التطبيق",
            "read_at": "2025-12-10T10:05:00Z",
            "created_at": "2025-12-10T10:00:00Z"
        },
        {
            "id": 2,
            "sender_id": 1,
            "sender_type": "App\\Models\\User",
            "message": "أهلاً بك، كيف نقدر نساعدك؟",
            "read_at": "2025-12-10T10:10:00Z",
            "created_at": "2025-12-10T10:05:00Z"
        }
    ]
}
```

---

**🔵 الرد على العميل:**
```http
POST /api/admin/support/reply
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "user_id": 5,
    "message": "تم حل المشكلة، شكراً لتواصلك"
}
```

**Response:**
```json
{
    "message": "تم إرسال الرد بنجاح",
    "data": {
        "id": 15,
        "conversation_id": "support-uuid-1",
        "message": "تم حل المشكلة، شكراً لتواصلك",
        "admin_id": 1,
        "admin_name": "Admin Name",
        "created_at": "2025-12-10T10:30:00Z"
    }
}
```

---

**🔵 تحديد المحادثة كمقروءة:**
```http
PATCH /api/admin/support/{user_id}/read
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "message": "ok",
    "marked_count": 2
}
```

---

**🔵 إحصائيات الدعم:**
```http
GET /api/admin/support/stats
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
    "total_conversations": 35,
    "unread_conversations": 5,
    "today_messages": 12,
    "avg_response_time": null
}
```

---

## 📋 ملخص سريع

### صفحة مراقبة المحادثات:

| الوظيفة | الـ Endpoint |
|---------|-------------|
| قائمة المحادثات | `GET /api/admin/monitoring/conversations` |
| عرض محادثة | `GET /api/admin/monitoring/conversations/{conversationId}` |
| بحث | `GET /api/admin/monitoring/search?q=...` |
| إحصائيات | `GET /api/admin/monitoring/stats` |

### صفحة الدعم الفني:

| الوظيفة | الـ Endpoint |
|---------|-------------|
| قائمة طلبات الدعم | `GET /api/admin/support/inbox` |
| عرض محادثة | `GET /api/admin/support/{user_id}` |
| الرد على العميل | `POST /api/admin/support/reply` |
| تحديد كمقروء | `PATCH /api/admin/support/{user_id}/read` |
| إحصائيات | `GET /api/admin/support/stats` |

> **ملاحظة:** كل الـ endpoints تحتاج `Authorization: Bearer {admin_token}` في الـ Header.
> 
> **هام:** صفحة المراقبة للقراءة فقط، بينما صفحة الدعم تسمح بالرد.
