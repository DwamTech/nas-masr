# دليل استخدام ميزة عرض الإعلان في المحادثة

## نظرة عامة

هذه الميزة تتيح عرض بطاقة مصغرة للإعلان داخل المحادثة بين المستخدمين، بحيث يكون واضحاً للطرفين عن أي إعلان يتحدثون.

---

## السيناريو الكامل

```
┌──────────────────────────────────────────────────────────────────┐
│                         تدفق العملية                             │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. مستخدم (أ) يشاهد إعلان المستخدم (ب)                          │
│           ↓                                                      │
│  2. إشعار مشاهدة يُرسل للمستخدم (ب)                              │
│     {listing_id, category_slug, viewer_id}                       │
│           ↓                                                      │
│  3. المستخدم (ب) يضغط على الإشعار                                │
│           ↓                                                      │
│  4. التطبيق يفتح شاشة المحادثة مع المستخدم (أ)                   │
│           ↓                                                      │
│  5. التطبيق يستدعي API ملخص الإعلان                              │
│           ↓                                                      │
│  6. عرض بطاقة الإعلان في أعلى المحادثة                           │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 1. إشعار المشاهدة

عندما يشاهد مستخدم إعلاناً، يتم إرسال إشعار لصاحب الإعلان يحتوي على البيانات التالية:

### شكل الإشعار (Notification Payload)

```json
{
  "id": 123,
  "title": "تمت مشاهدة إعلانك",
  "body": "قام المستخدم #456 بمشاهدة إعلانك #789 في قسم سيارات",
  "type": "view",
  "data": {
    "viewer_id": 456,
    "listing_id": 789,
    "category_slug": "cars"
  },
  "read_at": null,
  "created_at": "2025-12-15T01:15:00.000000Z"
}
```

### الحقول المهمة في `data`:

| الحقل | النوع | الوصف |
|-------|------|-------|
| `viewer_id` | Integer | ID المستخدم الذي شاهد الإعلان |
| `listing_id` | Integer | ID الإعلان الذي تمت مشاهدته |
| `category_slug` | String | الـ slug الخاص بالقسم (مثل: cars, real_estate, jobs) |

---

## 2. API ملخص الإعلان للشات

### Endpoint

```
GET /api/chat/listing-summary/{category_slug}/{listing_id}
```

### Headers

```
Authorization: Bearer {token}
```

### مثال على الطلب

```
GET /api/chat/listing-summary/cars/789
Authorization: Bearer eyJ0eX...
```

### الاستجابة الناجحة (200 OK)

```json
{
  "success": true,
  "data": {
    "id": 789,
    "category_slug": "cars",
    "category_name": "سيارات",
    "title": "سيارة BMW 520i موديل 2020",
    "price": 550000,
    "main_image_url": "https://nasmasr.com/storage/uploads/cars/main/abc123.jpg",
    "governorate": "القاهرة",
    "city": "مدينة نصر",
    "plan_type": "featured",
    "status": "Valid",
    "owner_id": 123
  }
}
```

### الاستجابة في حالة عدم وجود الإعلان (404)

```json
{
  "success": false,
  "message": "الإعلان غير موجود"
}
```

### شرح الحقول المرجعة:

| الحقل | النوع | الوصف |
|-------|------|-------|
| `id` | Integer | ID الإعلان |
| `category_slug` | String | slug القسم |
| `category_name` | String | اسم القسم بالعربية |
| `title` | String/null | عنوان/اسم الإعلان (أو جزء من الوصف) |
| `price` | Float/null | سعر الإعلان |
| `main_image_url` | String/null | رابط الصورة الرئيسية |
| `governorate` | String/null | اسم المحافظة |
| `city` | String/null | اسم المدينة |
| `plan_type` | String | نوع الخطة (featured, standard, free) |
| `status` | String | حالة الإعلان (Valid, Pending, Expired, Rejected) |
| `owner_id` | Integer | ID صاحب الإعلان |

---

## 3. تنفيذ Flutter المقترح

### 3.1 نموذج البيانات (Model)

```dart
class ListingCardModel {
  final int id;
  final String categorySlug;
  final String categoryName;
  final String? title;
  final double? price;
  final String? mainImageUrl;
  final String? governorate;
  final String? city;
  final String planType;
  final String status;
  final int ownerId;

  ListingCardModel({
    required this.id,
    required this.categorySlug,
    required this.categoryName,
    this.title,
    this.price,
    this.mainImageUrl,
    this.governorate,
    this.city,
    required this.planType,
    required this.status,
    required this.ownerId,
  });

  factory ListingCardModel.fromJson(Map<String, dynamic> json) {
    return ListingCardModel(
      id: json['id'],
      categorySlug: json['category_slug'],
      categoryName: json['category_name'],
      title: json['title'],
      price: json['price']?.toDouble(),
      mainImageUrl: json['main_image_url'],
      governorate: json['governorate'],
      city: json['city'],
      planType: json['plan_type'] ?? 'free',
      status: json['status'] ?? 'Valid',
      ownerId: json['owner_id'],
    );
  }
}
```

### 3.2 استدعاء الـ API

```dart
Future<ListingCardModel?> getListingSummary(String categorySlug, int listingId) async {
  try {
    final response = await dio.get(
      '/api/chat/listing-summary/$categorySlug/$listingId',
    );
    
    if (response.data['success'] == true) {
      return ListingCardModel.fromJson(response.data['data']);
    }
    return null;
  } catch (e) {
    print('Error fetching listing summary: $e');
    return null;
  }
}
```

### 3.3 التعامل مع الإشعار

```dart
void handleViewNotification(Map<String, dynamic> notification) {
  final data = notification['data'];
  final type = notification['type'];
  
  if (type == 'view') {
    final viewerId = data['viewer_id'];
    final listingId = data['listing_id'];
    final categorySlug = data['category_slug'];
    
    // فتح شاشة المحادثة مع المشاهد
    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => ChatScreen(
          otherUserId: viewerId,
          listingId: listingId,          // تمرير ID الإعلان
          categorySlug: categorySlug,    // تمرير slug القسم
        ),
      ),
    );
  }
}
```

### 3.4 شاشة المحادثة

```dart
class ChatScreen extends StatefulWidget {
  final int otherUserId;
  final int? listingId;        // اختياري - لو جاي من إشعار مشاهدة
  final String? categorySlug;  // اختياري - لو جاي من إشعار مشاهدة

  const ChatScreen({
    required this.otherUserId,
    this.listingId,
    this.categorySlug,
  });

  @override
  _ChatScreenState createState() => _ChatScreenState();
}

class _ChatScreenState extends State<ChatScreen> {
  ListingCardModel? listingCard;
  bool isLoadingCard = true;

  @override
  void initState() {
    super.initState();
    _loadListingCard();
  }

  Future<void> _loadListingCard() async {
    // لو فيه بيانات إعلان، جيب الملخص
    if (widget.listingId != null && widget.categorySlug != null) {
      final card = await getListingSummary(
        widget.categorySlug!,
        widget.listingId!,
      );
      setState(() {
        listingCard = card;
        isLoadingCard = false;
      });
    } else {
      setState(() {
        isLoadingCard = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('المحادثة')),
      body: Column(
        children: [
          // ✅ بطاقة الإعلان (لو موجودة)
          if (listingCard != null)
            ListingCardWidget(listing: listingCard!),
          
          // باقي المحادثة
          Expanded(
            child: MessagesList(userId: widget.otherUserId),
          ),
          
          // حقل الإرسال
          MessageInputField(receiverId: widget.otherUserId),
        ],
      ),
    );
  }
}
```

### 3.5 ويدجت بطاقة الإعلان

```dart
class ListingCardWidget extends StatelessWidget {
  final ListingCardModel listing;

  const ListingCardWidget({required this.listing});

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: EdgeInsets.all(12),
      padding: EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.grey[100],
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Colors.grey[300]!),
      ),
      child: Row(
        children: [
          // الصورة
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: listing.mainImageUrl != null
                ? Image.network(
                    listing.mainImageUrl!,
                    width: 80,
                    height: 80,
                    fit: BoxFit.cover,
                  )
                : Container(
                    width: 80,
                    height: 80,
                    color: Colors.grey[300],
                    child: Icon(Icons.image, color: Colors.grey),
                  ),
          ),
          SizedBox(width: 12),
          
          // التفاصيل
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // القسم
                Container(
                  padding: EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                  decoration: BoxDecoration(
                    color: Colors.blue[100],
                    borderRadius: BorderRadius.circular(4),
                  ),
                  child: Text(
                    listing.categoryName,
                    style: TextStyle(fontSize: 10, color: Colors.blue[800]),
                  ),
                ),
                SizedBox(height: 4),
                
                // العنوان
                Text(
                  listing.title ?? 'إعلان',
                  style: TextStyle(fontWeight: FontWeight.bold),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
                SizedBox(height: 4),
                
                // السعر والموقع
                Row(
                  children: [
                    if (listing.price != null)
                      Text(
                        '${listing.price!.toStringAsFixed(0)} ج.م',
                        style: TextStyle(
                          color: Colors.green[700],
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    Spacer(),
                    if (listing.governorate != null)
                      Text(
                        listing.governorate!,
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey[600],
                        ),
                      ),
                  ],
                ),
              ],
            ),
          ),
          
          // أيقونة الانتقال للإعلان
          IconButton(
            icon: Icon(Icons.arrow_forward_ios, size: 16),
            onPressed: () {
              // الانتقال لصفحة تفاصيل الإعلان
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (context) => ListingDetailsScreen(
                    categorySlug: listing.categorySlug,
                    listingId: listing.id,
                  ),
                ),
              );
            },
          ),
        ],
      ),
    );
  }
}
```

---

## 4. ملاحظات هامة

### 4.1 متى تظهر البطاقة؟
- **تظهر** فقط عندما يفتح المستخدم المحادثة من إشعار "تمت مشاهدة إعلانك"
- **لا تظهر** إذا فتح المحادثة من الـ Inbox العادي (بدون context إعلان)

### 4.2 الأقسام التي تستخدم صورة افتراضية:
- `jobs` (وظائف)
- `doctors` (أطباء)
- `teachers` (معلمين)

هذه الأقسام لا تحتوي على صور للإعلانات، لذلك يتم عرض صورة افتراضية للقسم.

### 4.3 التخزين المؤقت (Caching)
يُفضل تخزين بيانات البطاقة محلياً لتجنب استدعاء الـ API في كل مرة يتم فتح نفس المحادثة.

### 4.4 حالة الإعلان المحذوف
إذا تم حذف الإعلان بعد إرسال الإشعار، الـ API سيرجع `404` - يجب التعامل مع هذه الحالة بإخفاء البطاقة أو عرض رسالة "الإعلان لم يعد متاحاً".

---

## 5. التحسينات المستقبلية المقترحة

1. **إضافة زر "بدء محادثة عن هذا الإعلان"** في صفحة تفاصيل الإعلان
2. **حفظ الـ listing_id مع المحادثة** في الـ Backend لعرض البطاقة دائماً
3. **إرسال رسالة تلقائية** تحتوي على بيانات الإعلان كأول رسالة

---

## 6. ملخص الـ APIs المستخدمة

| الغرض | Method | Endpoint |
|-------|--------|----------|
| قائمة الإشعارات | GET | `/api/notifications` |
| ملخص الإعلان للشات | GET | `/api/chat/listing-summary/{category_slug}/{listing_id}` |
| إرسال رسالة | POST | `/api/chat/send` |
| سجل المحادثة | GET | `/api/chat/{user_id}` |

---

## 7. معلومات التواصل

للاستفسارات أو المشاكل التقنية، تواصل مع فريق الـ Backend.

**تاريخ الإنشاء:** 15 ديسمبر 2025
