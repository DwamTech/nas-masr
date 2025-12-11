# 📊 Dashboard Reports API Documentation

## نظرة عامة

هذا المستند يوثق جميع APIs الخاصة بنظام التقارير للوحة التحكم (Dashboard). جميع الـ Endpoints تتطلب صلاحيات Admin وتستخدم Bearer Token للمصادقة.

**Base URL**: `/api/admin`

**Headers المطلوبة**:
```http
Authorization: Bearer {admin_token}
Content-Type: application/json
Accept: application/json
```

---

## 📑 فهرس المحتويات

1. [تقارير المستخدمين](#1-تقارير-المستخدمين-users-reports)
2. [تقارير الإعلانات](#2-تقارير-الإعلانات-ads-reports)
3. [تقارير المعلنين](#3-تقارير-المعلنين-advertisers-reports)
4. [تقارير المالية والمعاملات](#4-تقارير-المالية-والمعاملات-financial-reports)
5. [تقارير الأنشطة](#5-تقارير-الأنشطة-activity-reports)

---

## 1. تقارير المستخدمين (Users Reports)

### 1.1 ملخص المستخدمين

**Endpoint**: `GET /api/admin/users-summary`

**الوصف**: جلب قائمة المستخدمين مع إمكانية الفلترة والبحث

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 20 | عدد النتائج في الصفحة |
| `role` | string | ❌ | - | فلتر حسب الدور: `admin`, `user`, `reviewer`, `advertiser` |
| `status` | string | ❌ | - | فلتر حسب الحالة: `active`, `blocked` |
| `q` | string | ❌ | - | بحث بالاسم أو رقم الهاتف أو كود الإحالة |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  },
  "users": [
    {
      "id": 1,
      "name": "أحمد محمد",
      "phone": "01012345678",
      "user_code": "REF123",
      "status": "active",
      "registered_at": "2025-01-15",
      "listings_count": 5,
      "role": "user"
    }
  ]
}
```

---

### 1.2 إحصائيات تسجيلات الدخول الشهرية (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/users/login-stats`

**الوصف**: إحصائيات تسجيلات الدخول حسب الشهر

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `year` | integer | ❌ | السنة الحالية | السنة المطلوبة |
| `month` | integer | ❌ | - | الشهر (1-12)، إذا لم يُحدد يرجع كل الشهور |

**Response**:
```json
{
  "year": 2025,
  "monthly_stats": [
    {
      "month": 1,
      "month_name": "يناير",
      "total_logins": 1250,
      "unique_users": 890,
      "avg_logins_per_user": 1.4
    },
    {
      "month": 2,
      "month_name": "فبراير",
      "total_logins": 1580,
      "unique_users": 920,
      "avg_logins_per_user": 1.7
    }
  ],
  "total_year_logins": 15000,
  "growth_rate": 12.5
}
```

> [!IMPORTANT]
> هذا الـ Endpoint يتطلب تتبع جلسات الدخول في جدول `sessions` أو إنشاء جدول `login_logs` جديد

---

### 1.3 المستخدمين النشطين (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/users/active`

**الوصف**: جلب قائمة المستخدمين النشطين (لديهم نشاط في آخر 30 يوم)

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 20 | عدد النتائج في الصفحة |
| `days` | integer | ❌ | 30 | عدد الأيام لتحديد النشاط |
| `activity_type` | string | ❌ | - | نوع النشاط: `login`, `listing`, `chat`, `all` |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 450,
    "last_page": 23
  },
  "summary": {
    "total_active_users": 450,
    "total_users": 1500,
    "activity_rate": 30.0,
    "comparison_previous_period": {
      "count": 420,
      "percent_change": 7.14,
      "direction": "up"
    }
  },
  "users": [
    {
      "id": 1,
      "name": "أحمد محمد",
      "phone": "01012345678",
      "last_activity_at": "2025-12-11T10:30:00Z",
      "activity_type": "listing_created",
      "total_activities": 15,
      "listings_count": 5
    }
  ]
}
```

---

### 1.4 المستخدمين المحظورين

**Endpoint**: `GET /api/admin/reports/users/blocked`

**الوصف**: جلب قائمة المستخدمين المحظورين

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 20 | عدد النتائج في الصفحة |
| `blocked_after` | date | ❌ | - | فلتر المحظورين بعد تاريخ معين |
| `blocked_before` | date | ❌ | - | فلتر المحظورين قبل تاريخ معين |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 25,
    "last_page": 2
  },
  "summary": {
    "total_blocked": 25,
    "blocked_this_month": 5,
    "blocked_rate": 1.67
  },
  "users": [
    {
      "id": 50,
      "name": "مستخدم محظور",
      "phone": "01098765432",
      "status": "blocked",
      "blocked_at": "2025-12-01T14:00:00Z",
      "block_reason": "مخالفة شروط الاستخدام",
      "listings_count": 3,
      "reported_count": 5
    }
  ]
}
```

---

### 1.5 إحصائيات التسجيل الجديد (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/users/registrations`

**الوصف**: إحصائيات التسجيلات الجديدة حسب الفترة الزمنية

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `period` | string | ❌ | monthly | الفترة: `daily`, `weekly`, `monthly`, `yearly` |
| `from` | date | ❌ | - | تاريخ البداية |
| `to` | date | ❌ | - | تاريخ النهاية |

**Response**:
```json
{
  "period": "monthly",
  "data": [
    {
      "date": "2025-01",
      "label": "يناير 2025",
      "registrations": 120,
      "verified": 95,
      "verification_rate": 79.2
    },
    {
      "date": "2025-02",
      "label": "فبراير 2025",
      "registrations": 145,
      "verified": 130,
      "verification_rate": 89.6
    }
  ],
  "totals": {
    "total_registrations": 1500,
    "total_verified": 1350,
    "avg_monthly": 125,
    "growth_rate": 8.5
  }
}
```

---

### 1.6 توزيع المستخدمين حسب الدور (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/users/by-role`

**الوصف**: توزيع المستخدمين حسب أدوارهم

**Response**:
```json
{
  "total_users": 1500,
  "by_role": [
    {
      "role": "user",
      "role_name": "مستخدم",
      "count": 1200,
      "percentage": 80.0
    },
    {
      "role": "advertiser",
      "role_name": "معلن",
      "count": 250,
      "percentage": 16.67
    },
    {
      "role": "admin",
      "role_name": "مسؤول",
      "count": 5,
      "percentage": 0.33
    },
    {
      "role": "reviewer",
      "role_name": "مراجع",
      "count": 45,
      "percentage": 3.0
    }
  ]
}
```

---

## 2. تقارير الإعلانات (Ads Reports)

### 2.1 إحصائيات الإعلانات الرئيسية (موجود حالياً)

**Endpoint**: `GET /api/admin/stats`

**الوصف**: إحصائيات شاملة للإعلانات مع مقارنة شهرية

**Response**:
```json
{
  "cards": {
    "rejected": {
      "count": 50,
      "percent": -10.5,
      "direction": "down"
    },
    "pending": {
      "count": 120,
      "percent": 15.2,
      "direction": "up"
    },
    "active": {
      "count": 850,
      "percent": 8.5,
      "direction": "up"
    },
    "total": {
      "count": 1500,
      "percent": 12.0,
      "direction": "up"
    }
  },
  "periods": {
    "current_month": {
      "start": "2025-12-01",
      "end": "2025-12-31"
    },
    "previous_month": {
      "start": "2025-11-01",
      "end": "2025-11-30"
    }
  }
}
```

---

### 2.2 الإعلانات النشطة (المنشورة)

**Endpoint**: `GET /api/admin/published-listings`

**الوصف**: جلب الإعلانات المنشورة والنشطة

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 20 | عدد النتائج في الصفحة |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 850,
    "last_page": 43
  },
  "listings": [
    {
      "status": "منشور",
      "id": 100,
      "category_slug": "cars",
      "category_name": "سيارات",
      "published_at": "2025-12-01",
      "expire_at": "2026-12-01",
      "plan_type": "featured",
      "price": 150000.00,
      "views": 250,
      "advertiser_id": 25,
      "advertiser_phone": "01012345678"
    }
  ]
}
```

---

### 2.3 الإعلانات المرفوضة

**Endpoint**: `GET /api/admin/rejected-listings`

**الوصف**: جلب الإعلانات المرفوضة مع أسباب الرفض

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 50 | عدد النتائج في الصفحة |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 50,
    "total": 50,
    "last_page": 1
  },
  "listings": [
    {
      "status": "مرفوض",
      "id": 200,
      "category_name": "سيارات",
      "category_slug": "cars",
      "created_at": "2025-12-05",
      "expire_at": null,
      "rejected_by": "مشرف النظام",
      "rejection_reason": "صور غير واضحة",
      "advertiser_id": 30,
      "advertiser_phone": "01098765432",
      "views": 10
    }
  ]
}
```

---

### 2.4 الإعلانات قيد المراجعة

**Endpoint**: `GET /api/admin/pending-listings`

**الوصف**: جلب الإعلانات المعلقة التي تحتاج مراجعة

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 50 | عدد النتائج في الصفحة |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 50,
    "total": 120,
    "last_page": 3
  },
  "listings": [
    {
      "id": 300,
      "title": "سيارة للبيع",
      "category_id": 1,
      "status": "Pending",
      "created_at": "2025-12-10T10:00:00Z",
      "user": {
        "id": 40,
        "name": "محمد أحمد",
        "phone": "01055555555"
      },
      "governorate": {
        "id": 1,
        "name": "القاهرة"
      },
      "city": {
        "id": 5,
        "name": "مدينة نصر"
      }
    }
  ]
}
```

---

### 2.5 الإعلانات غير المدفوعة

**Endpoint**: `GET /api/admin/ads-not-payment`

**الوصف**: جلب الإعلانات التي لم يتم دفعها

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 50 | عدد النتائج في الصفحة |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 50,
    "total": 30,
    "last_page": 1
  },
  "listings": [
    {
      "id": 400,
      "title": "شقة للإيجار",
      "status": "Pending",
      "isPayment": false,
      "created_at": "2025-12-09T15:00:00Z",
      "user": {
        "id": 55,
        "name": "سارة أحمد"
      }
    }
  ]
}
```

---

### 2.6 عدد الإعلانات حسب القسم (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/ads/by-category`

**الوصف**: توزيع الإعلانات على الأقسام المختلفة

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | ❌ | - | فلتر حسب الحالة: `Valid`, `Pending`, `Rejected`, `Expired` |
| `include_inactive` | boolean | ❌ | false | تضمين الأقسام غير النشطة |

**Response**:
```json
{
  "total_ads": 1500,
  "total_categories": 8,
  "categories": [
    {
      "category_id": 1,
      "category_slug": "cars",
      "category_name": "سيارات",
      "icon_url": "https://example.com/icons/cars.png",
      "total_ads": 450,
      "percentage": 30.0,
      "breakdown": {
        "active": 380,
        "pending": 50,
        "rejected": 10,
        "expired": 10
      }
    },
    {
      "category_id": 2,
      "category_slug": "real-estate",
      "category_name": "عقارات",
      "icon_url": "https://example.com/icons/real-estate.png",
      "total_ads": 320,
      "percentage": 21.3,
      "breakdown": {
        "active": 280,
        "pending": 30,
        "rejected": 5,
        "expired": 5
      }
    },
    {
      "category_id": 3,
      "category_slug": "doctors",
      "category_name": "أطباء",
      "icon_url": "https://example.com/icons/doctors.png",
      "total_ads": 180,
      "percentage": 12.0,
      "breakdown": {
        "active": 160,
        "pending": 15,
        "rejected": 3,
        "expired": 2
      }
    }
  ]
}
```

---

### 2.7 تحليل الإعلانات الزمني (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/ads/timeline`

**الوصف**: تحليل الإعلانات عبر الزمن

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `period` | string | ❌ | daily | الفترة: `daily`, `weekly`, `monthly` |
| `from` | date | ❌ | آخر 30 يوم | تاريخ البداية |
| `to` | date | ❌ | اليوم | تاريخ النهاية |
| `category_id` | integer | ❌ | - | فلتر بقسم محدد |

**Response**:
```json
{
  "period": "daily",
  "from": "2025-12-01",
  "to": "2025-12-11",
  "data": [
    {
      "date": "2025-12-01",
      "created": 25,
      "approved": 20,
      "rejected": 3,
      "expired": 2
    },
    {
      "date": "2025-12-02",
      "created": 30,
      "approved": 28,
      "rejected": 1,
      "expired": 1
    }
  ],
  "totals": {
    "total_created": 280,
    "total_approved": 250,
    "total_rejected": 15,
    "total_expired": 15,
    "approval_rate": 89.3
  }
}
```

---

### 2.8 أكثر الإعلانات مشاهدة (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/ads/most-viewed`

**الوصف**: جلب الإعلانات الأكثر مشاهدة

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | ❌ | 20 | عدد النتائج |
| `category_id` | integer | ❌ | - | فلتر بقسم محدد |
| `period` | string | ❌ | all | الفترة: `today`, `week`, `month`, `all` |

**Response**:
```json
{
  "period": "month",
  "listings": [
    {
      "id": 100,
      "title": "BMW 2024 للبيع",
      "category_name": "سيارات",
      "views": 5000,
      "price": 2500000.00,
      "advertiser_name": "أحمد محمد",
      "published_at": "2025-12-01",
      "engagement_rate": 15.5
    }
  ],
  "total_views": 150000,
  "avg_views_per_ad": 176
}
```

---

### 2.9 إحصائيات خطط الإعلانات (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/ads/by-plan`

**الوصف**: توزيع الإعلانات حسب نوع الخطة

**Response**:
```json
{
  "total_ads": 1500,
  "by_plan": [
    {
      "plan_type": "featured",
      "plan_name": "مميز",
      "count": 200,
      "percentage": 13.3,
      "total_revenue": 50000.00,
      "avg_views": 350
    },
    {
      "plan_type": "standard",
      "plan_name": "عادي",
      "count": 1300,
      "percentage": 86.7,
      "total_revenue": 130000.00,
      "avg_views": 150
    }
  ]
}
```

---

## 3. تقارير المعلنين (Advertisers Reports)

### 3.1 ملخص المعلنين (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/advertisers/summary`

**الوصف**: ملخص شامل لإحصائيات المعلنين

**Response**:
```json
{
  "total_advertisers": 250,
  "new_this_month": 25,
  "growth_rate": 11.1,
  "total_ads": 1500,
  "total_spending": 180000.00,
  "currency": "EGP",
  "avg_ads_per_advertiser": 6.0,
  "avg_spending_per_advertiser": 720.00,
  "top_category": {
    "name": "سيارات",
    "percentage": 30.0
  }
}
```

---

### 3.2 إجمالي الإنفاق

**Endpoint**: `GET /api/admin/reports/advertisers/spending`

**الوصف**: تقرير تفصيلي عن إنفاق المعلنين

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `from` | date | ❌ | - | تاريخ البداية |
| `to` | date | ❌ | - | تاريخ النهاية |
| `category_id` | integer | ❌ | - | فلتر بقسم محدد |
| `plan_type` | string | ❌ | - | نوع الخطة: `featured`, `standard` |

**Response**:
```json
{
  "period": {
    "from": "2025-12-01",
    "to": "2025-12-31"
  },
  "summary": {
    "total_spending": 180000.00,
    "total_transactions": 450,
    "avg_transaction": 400.00,
    "currency": "EGP"
  },
  "by_type": {
    "ad_payments": {
      "count": 350,
      "total": 120000.00
    },
    "subscriptions": {
      "count": 100,
      "total": 60000.00
    }
  },
  "by_plan": [
    {
      "plan_type": "featured",
      "total": 80000.00,
      "count": 150,
      "percentage": 44.4
    },
    {
      "plan_type": "standard",
      "total": 100000.00,
      "count": 300,
      "percentage": 55.6
    }
  ],
  "by_category": [
    {
      "category_id": 1,
      "category_name": "سيارات",
      "total": 60000.00,
      "count": 150,
      "percentage": 33.3
    }
  ],
  "trend": [
    {
      "date": "2025-12-01",
      "amount": 5000.00
    },
    {
      "date": "2025-12-02",
      "amount": 6500.00
    }
  ]
}
```

---

### 3.3 قائمة المعلنين (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/advertisers/list`

**الوصف**: قائمة تفصيلية بجميع المعلنين

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 20 | عدد النتائج في الصفحة |
| `sort_by` | string | ❌ | spending | الترتيب: `spending`, `ads_count`, `views`, `recent` |
| `order` | string | ❌ | desc | اتجاه الترتيب: `asc`, `desc` |
| `category_id` | integer | ❌ | - | فلتر بقسم محدد |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 250,
    "last_page": 13
  },
  "advertisers": [
    {
      "id": 1,
      "name": "شركة السيارات المتحدة",
      "phone": "01012345678",
      "email": "info@cars.com",
      "registered_at": "2025-01-15",
      "status": "active",
      "stats": {
        "total_ads": 50,
        "active_ads": 45,
        "pending_ads": 3,
        "rejected_ads": 2,
        "total_views": 25000,
        "total_spending": 15000.00
      },
      "package": {
        "featured_remaining": 5,
        "standard_remaining": 10,
        "expires_at": "2026-01-15"
      }
    }
  ]
}
```

---

### 3.4 أفضل المعلنين (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/advertisers/top`

**الوصف**: قائمة بأفضل المعلنين حسب معايير مختلفة

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | ❌ | 10 | عدد النتائج |
| `metric` | string | ❌ | spending | المعيار: `spending`, `ads`, `views`, `engagement` |
| `period` | string | ❌ | month | الفترة: `week`, `month`, `quarter`, `year`, `all` |

**Response**:
```json
{
  "metric": "spending",
  "period": "month",
  "advertisers": [
    {
      "rank": 1,
      "id": 25,
      "name": "مجموعة الفخامة للسيارات",
      "phone": "01155555555",
      "total_spending": 25000.00,
      "ads_count": 30,
      "total_views": 15000,
      "avg_views_per_ad": 500,
      "badge": "platinum"
    },
    {
      "rank": 2,
      "id": 30,
      "name": "عقارات المستقبل",
      "phone": "01199999999",
      "total_spending": 18000.00,
      "ads_count": 25,
      "total_views": 12000,
      "avg_views_per_ad": 480,
      "badge": "gold"
    }
  ]
}
```

---

### 3.5 إحصائيات الباقات (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/advertisers/packages`

**الوصف**: تقرير عن استخدام الباقات

**Response**:
```json
{
  "total_packages": 150,
  "active_packages": 120,
  "expired_packages": 30,
  "usage_stats": {
    "featured": {
      "total_allocated": 500,
      "total_used": 350,
      "usage_rate": 70.0
    },
    "standard": {
      "total_allocated": 1500,
      "total_used": 1100,
      "usage_rate": 73.3
    }
  },
  "expiring_soon": {
    "in_7_days": 15,
    "in_30_days": 45
  }
}
```

---

## 4. تقارير المالية والمعاملات (Financial Reports)

### 4.1 سجل المعاملات (موجود حالياً)

**Endpoint**: `GET /api/admin/transactions`

**الوصف**: جلب جميع المعاملات المالية

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 100 | عدد النتائج في الصفحة |
| `user_id` | integer | ❌ | - | فلتر بمستخدم محدد |
| `category_id` | integer | ❌ | - | فلتر بقسم محدد |
| `plan_type` | string | ❌ | - | نوع الخطة |
| `from` | date | ❌ | - | تاريخ البداية |
| `to` | date | ❌ | - | تاريخ النهاية |

**Response**:
```json
{
  "ads": {
    "meta": {
      "page": 1,
      "per_page": 100,
      "total": 350,
      "last_page": 4
    },
    "items": [
      {
        "type": "ad_payment",
        "id": 1,
        "user_id": 25,
        "user_name": "أحمد محمد",
        "listing_id": 100,
        "listing_title": "BMW للبيع",
        "category_id": 1,
        "plan_type": "featured",
        "amount": 500.00,
        "currency": "EGP",
        "paid_at": "2025-12-10T14:00:00Z",
        "payment_method": "card",
        "payment_reference": "TXN123456",
        "status": "paid"
      }
    ]
  },
  "subscriptions": {
    "meta": {
      "page": 1,
      "per_page": 100,
      "total": 100,
      "last_page": 1
    },
    "items": [
      {
        "type": "subscription",
        "id": 1,
        "user_id": 30,
        "user_name": "سارة علي",
        "category_id": 2,
        "plan_type": "standard",
        "price": 1000.00,
        "ad_price": 100.00,
        "payment_method": "wallet",
        "payment_reference": "SUB789",
        "subscribed_at": "2025-12-01T10:00:00Z",
        "expires_at": "2026-12-01T10:00:00Z"
      }
    ]
  }
}
```

---

### 4.2 ملخص الإيرادات (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/financial/revenue`

**الوصف**: ملخص شامل للإيرادات

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `period` | string | ❌ | monthly | الفترة: `daily`, `weekly`, `monthly`, `yearly` |
| `from` | date | ❌ | - | تاريخ البداية |
| `to` | date | ❌ | - | تاريخ النهاية |

**Response**:
```json
{
  "period": "monthly",
  "currency": "EGP",
  "summary": {
    "total_revenue": 180000.00,
    "previous_period": 160000.00,
    "growth_rate": 12.5,
    "direction": "up"
  },
  "breakdown": {
    "ad_payments": 120000.00,
    "subscriptions": 60000.00
  },
  "by_plan": {
    "featured": 80000.00,
    "standard": 100000.00
  },
  "by_category": [
    {
      "category_name": "سيارات",
      "revenue": 60000.00,
      "percentage": 33.3
    }
  ],
  "chart_data": [
    {
      "label": "ديسمبر 2025",
      "value": 180000.00
    },
    {
      "label": "نوفمبر 2025",
      "value": 160000.00
    }
  ]
}
```

---

### 4.3 طرق الدفع (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/financial/payment-methods`

**الوصف**: تحليل طرق الدفع المستخدمة

**Response**:
```json
{
  "total_transactions": 450,
  "total_amount": 180000.00,
  "methods": [
    {
      "method": "card",
      "method_name": "بطاقة ائتمان",
      "count": 250,
      "amount": 100000.00,
      "percentage": 55.6
    },
    {
      "method": "wallet",
      "method_name": "محفظة إلكترونية",
      "count": 150,
      "amount": 60000.00,
      "percentage": 33.3
    },
    {
      "method": "cash",
      "method_name": "نقدي",
      "count": 50,
      "amount": 20000.00,
      "percentage": 11.1
    }
  ]
}
```

---

## 5. تقارير الأنشطة (Activity Reports)

### 5.1 الأنشطة الأخيرة (موجود حالياً)

**Endpoint**: `GET /api/admin/recent-activities`

**الوصف**: جلب آخر الأنشطة في النظام

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `limit` | integer | ❌ | 20 | عدد النتائج |

**Response**:
```json
{
  "count": 20,
  "activities": [
    {
      "type": "listing_approved",
      "message": "تم تفعيل إعلان",
      "entity": "listing",
      "id": 300,
      "status": "Valid",
      "admin_approved": true,
      "timestamp": "2025-12-11T12:00:00Z",
      "ago": "منذ ساعتين"
    },
    {
      "type": "listing_rejected",
      "message": "تم رفض إعلان",
      "entity": "listing",
      "id": 301,
      "status": "Rejected",
      "admin_approved": false,
      "timestamp": "2025-12-11T11:30:00Z",
      "ago": "منذ ساعتين ونصف"
    },
    {
      "type": "settings_updated",
      "message": "تم تحديث الإعدادات",
      "entity": "system_settings",
      "id": 1,
      "timestamp": "2025-12-11T10:00:00Z",
      "ago": "منذ 4 ساعات"
    }
  ]
}
```

---

### 5.2 تقارير البلاغات (موجود حالياً)

**Endpoint**: `GET /api/admin/listing-reports`

**الوصف**: جلب بلاغات الإعلانات

**Response**:
```json
{
  "reports": [
    {
      "id": 1,
      "listing_id": 100,
      "listing_title": "إعلان مخالف",
      "reporter_id": 50,
      "reporter_name": "مستخدم",
      "reason": "محتوى مخالف",
      "status": "pending",
      "created_at": "2025-12-10T14:00:00Z"
    }
  ]
}
```

---

### 5.3 سجل إجراءات المشرفين (🆕 مقترح)

**Endpoint**: `GET /api/admin/reports/activity/admin-actions`

**الوصف**: سجل جميع الإجراءات التي قام بها المشرفون

**Query Parameters**:
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `per_page` | integer | ❌ | 50 | عدد النتائج في الصفحة |
| `admin_id` | integer | ❌ | - | فلتر بمشرف محدد |
| `action_type` | string | ❌ | - | نوع الإجراء: `approve`, `reject`, `block`, `unblock` |
| `from` | date | ❌ | - | تاريخ البداية |
| `to` | date | ❌ | - | تاريخ النهاية |

**Response**:
```json
{
  "meta": {
    "page": 1,
    "per_page": 50,
    "total": 500,
    "last_page": 10
  },
  "actions": [
    {
      "id": 1,
      "admin_id": 1,
      "admin_name": "المشرف العام",
      "action_type": "listing_approved",
      "action_label": "موافقة على إعلان",
      "target_type": "listing",
      "target_id": 300,
      "target_title": "BMW للبيع",
      "details": null,
      "ip_address": "192.168.1.1",
      "created_at": "2025-12-11T12:00:00Z"
    },
    {
      "id": 2,
      "admin_id": 1,
      "admin_name": "المشرف العام",
      "action_type": "user_blocked",
      "action_label": "حظر مستخدم",
      "target_type": "user",
      "target_id": 50,
      "target_title": "مستخدم مخالف",
      "details": {
        "reason": "مخالفة شروط الاستخدام"
      },
      "ip_address": "192.168.1.1",
      "created_at": "2025-12-11T11:00:00Z"
    }
  ]
}
```

---

## 📌 ملاحظات للمطور

### Endpoints الموجودة حالياً:
- ✅ `GET /api/admin/stats` - إحصائيات الإعلانات
- ✅ `GET /api/admin/recent-activities` - الأنشطة الأخيرة
- ✅ `GET /api/admin/users-summary` - ملخص المستخدمين
- ✅ `GET /api/admin/pending-listings` - الإعلانات المعلقة
- ✅ `GET /api/admin/ads-not-payment` - الإعلانات غير المدفوعة
- ✅ `GET /api/admin/published-listings` - الإعلانات المنشورة
- ✅ `GET /api/admin/rejected-listings` - الإعلانات المرفوضة
- ✅ `GET /api/admin/transactions` - المعاملات
- ✅ `GET /api/admin/listing-reports` - بلاغات الإعلانات

### Endpoints المقترحة الجديدة (🆕):
| Endpoint | الأهمية | التعقيد |
|----------|---------|---------|
| `/reports/users/login-stats` | عالية | متوسط (يتطلب جدول جديد) |
| `/reports/users/active` | عالية | منخفض |
| `/reports/users/blocked` | متوسطة | منخفض |
| `/reports/users/registrations` | متوسطة | منخفض |
| `/reports/users/by-role` | منخفضة | منخفض |
| `/reports/ads/by-category` | عالية | منخفض |
| `/reports/ads/timeline` | عالية | متوسط |
| `/reports/ads/most-viewed` | متوسطة | منخفض |
| `/reports/ads/by-plan` | متوسطة | منخفض |
| `/reports/advertisers/summary` | عالية | منخفض |
| `/reports/advertisers/spending` | عالية | متوسط |
| `/reports/advertisers/list` | عالية | منخفض |
| `/reports/advertisers/top` | متوسطة | منخفض |
| `/reports/advertisers/packages` | متوسطة | منخفض |
| `/reports/financial/revenue` | عالية | متوسط |
| `/reports/financial/payment-methods` | متوسطة | منخفض |
| `/reports/activity/admin-actions` | عالية | متوسط (يتطلب جدول جديد) |

### متطلبات قاعدة البيانات للـ Endpoints الجديدة:

#### 1. جدول `login_logs` (لتتبع تسجيلات الدخول):
```sql
CREATE TABLE login_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    logged_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 2. جدول `admin_action_logs` (لسجل إجراءات المشرفين):
```sql
CREATE TABLE admin_action_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    admin_id BIGINT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id BIGINT NOT NULL,
    details JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);
```

#### 3. إضافة حقل `blocked_at` و `block_reason` لجدول `users`:
```sql
ALTER TABLE users 
ADD COLUMN blocked_at TIMESTAMP NULL,
ADD COLUMN block_reason VARCHAR(255) NULL;
```

---

## 🎨 اقتراحات لواجهة المستخدم (Dashboard UI)

### 1. صفحة الإحصائيات الرئيسية:
- بطاقات ملخصة (Cards) للأرقام الرئيسية
- رسوم بيانية للاتجاهات الزمنية
- دونات شارت لتوزيع الإعلانات على الأقسام

### 2. صفحة تقارير المستخدمين:
- جدول المستخدمين مع فلاتر وبحث
- رسم بياني للتسجيلات الشهرية
- قائمة المستخدمين المحظورين

### 3. صفحة تقارير الإعلانات:
- فلاتر حسب الحالة والقسم
- رسم بياني للإعلانات حسب القسم
- جدول أكثر الإعلانات مشاهدة

### 4. صفحة تقارير المعلنين:
- قائمة أفضل المعلنين
- رسم بياني للإنفاق
- تقرير استخدام الباقات

### 5. صفحة التقارير المالية:
- ملخص الإيرادات
- رسم بياني للاتجاهات المالية
- توزيع طرق الدفع

---

> [!TIP]
> يُفضل استخدام **Caching** للـ Endpoints الثقيلة لتحسين الأداء. مثلاً، يمكن تخزين نتائج `reports/ads/by-category` لمدة 5 دقائق.

> [!NOTE]
> جميع الـ Endpoints تدعم **Pagination** للنتائج الكبيرة باستخدام `page` و `per_page` parameters.
