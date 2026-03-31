# Backup System Updates

## التحديثات الجديدة

تم إضافة 3 وظائف جديدة لنظام النسخ الاحتياطي مع الحفاظ على البنية المعمارية النظيفة الموجودة.

---

## ✨ الوظائف الجديدة

### 1. تحميل ملف النسخة الاحتياطية (Download)
**Endpoint:** `GET /api/admin/backups/{id}/download`

يسمح للمسؤول بتحميل ملف النسخة الاحتياطية مباشرة.

**الاستخدام:**
```javascript
// Frontend example
const downloadBackup = async (backupId) => {
  const response = await fetch(`/api/admin/backups/${backupId}/download`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'backup.sql.gz';
  a.click();
};
```

---

### 2. رفع ملف نسخة احتياطية خارجية (Upload)
**Endpoint:** `POST /api/admin/backups/upload`

يسمح للمسؤول برفع ملف نسخة احتياطية من مصدر خارجي.

**المميزات:**
- ✅ دعم الملفات: `.gz`, `.sql`, `.zip`
- ✅ حد أقصى للحجم: 500 ميجابايت
- ✅ إعادة تسمية تلقائية لتجنب التعارض
- ✅ تسجيل تلقائي في قاعدة البيانات
- ✅ Validation شامل

**الاستخدام:**
```javascript
// Frontend example
const uploadBackup = async (file) => {
  const formData = new FormData();
  formData.append('file', file);
  
  const response = await fetch('/api/admin/backups/upload', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    },
    body: formData
  });
  
  return await response.json();
};
```

---

### 3. عرض سجل النسخ الاحتياطية (History)
**Endpoint:** `GET /api/admin/backups/history`

يعرض آخر 50 عملية من عمليات النسخ الاحتياطي.

**الاستخدام:**
```javascript
// Frontend example
const getBackupHistory = async () => {
  const response = await fetch('/api/admin/backups/history', {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const data = await response.json();
  return data.data; // Array of last 50 operations
};
```

---

## 📁 الملفات المعدلة

### 1. `app/Services/BackupService.php`
**Methods المضافة:**
- `getBackup(int $id)`: جلب نسخة احتياطية محددة
- `getBackupPath(BackupHistory $backup)`: الحصول على المسار الكامل للملف
- `uploadBackup(UploadedFile $file)`: رفع ملف نسخة احتياطية
- `getHistory(int $limit = 50)`: جلب سجل العمليات

### 2. `app/Http/Controllers/Admin/BackupController.php`
**Methods المضافة:**
- `download(int $id)`: تحميل ملف النسخة
- `upload(UploadBackupRequest $request)`: رفع ملف نسخة
- `history()`: عرض السجل

### 3. `app/Http/Requests/Admin/UploadBackupRequest.php` ✨ NEW
**Validation Rules:**
```php
[
    'file' => [
        'required',
        'file',
        'mimes:gz,sql,zip',
        'max:512000', // 500 MB
    ],
]
```

### 4. `routes/api.php`
**Routes المضافة:**
```php
Route::get('/history', [BackupController::class, 'history']);
Route::post('/upload', [BackupController::class, 'upload']);
Route::get('/{id}/download', [BackupController::class, 'download']);
```

---

## 🏗️ البنية المعمارية

تم الحفاظ على نفس البنية المعمارية النظيفة:

```
┌─────────────────────────────────────┐
│   BackupController                  │
│   (Handles HTTP requests)           │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   BackupService                     │
│   (Business logic)                  │
└──────────────┬──────────────────────┘
               │
               ▼
┌─────────────────────────────────────┐
│   BackupHistory Model               │
│   (Database operations)             │
└─────────────────────────────────────┘
```

**المميزات:**
- ✅ Separation of Concerns
- ✅ Single Responsibility Principle
- ✅ Easy to test
- ✅ Easy to maintain
- ✅ Consistent error handling
- ✅ Event-driven (BackupCreated event)

---

## 🔒 الأمان

### Upload Security
1. **File Type Validation**: فقط `.gz`, `.sql`, `.zip`
2. **File Size Limit**: حد أقصى 500 ميجابايت
3. **Unique Naming**: إعادة تسمية تلقائية بـ timestamp
4. **Authorization**: يتطلب admin middleware
5. **Error Handling**: معالجة شاملة للأخطاء

### Download Security
1. **Authorization**: يتطلب admin middleware
2. **File Existence Check**: التحقق من وجود الملف
3. **Path Validation**: استخدام Storage facade
4. **Error Handling**: معالجة 404 و 500

---

## 📊 Database Schema

لا توجد تغييرات على قاعدة البيانات. نفس الـ schema الموجود:

```sql
CREATE TABLE backup_histories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    size BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Backup Types:**
- `db`: نسخة احتياطية من قاعدة البيانات
- `upload`: ملف مرفوع من مصدر خارجي ✨ NEW

---

## 🧪 Testing

### Manual Testing

#### Test Upload
```bash
# Create a test backup file
echo "test backup content" > test_backup.sql
gzip test_backup.sql

# Upload it
curl -X POST \
  http://localhost/api/admin/backups/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@test_backup.sql.gz"
```

#### Test Download
```bash
curl -X GET \
  http://localhost/api/admin/backups/1/download \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o downloaded_backup.sql.gz
```

#### Test History
```bash
curl -X GET \
  http://localhost/api/admin/backups/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 📝 Logging

جميع العمليات يتم تسجيلها في الـ logs:

```
[BackupService] Backup uploaded. {"backup_id":2,"file":"backup_external.gz","actor":1}
[BackupService] Backup deleted. {"backup_id":1,"file":"backup_old.gz","actor":1}
```

---

## 🚀 Next Steps

### Recommended Enhancements (Optional)

1. **Scheduled Backups**: إضافة جدولة تلقائية للنسخ الاحتياطي
2. **Cloud Storage**: دعم رفع النسخ إلى S3 أو Google Cloud
3. **Backup Verification**: التحقق من صحة الملف المرفوع
4. **Compression Options**: خيارات ضغط مختلفة
5. **Email Notifications**: إشعارات بريد إلكتروني عند اكتمال النسخ

---

## 📞 Support

للمزيد من المعلومات، راجع:
- [API Documentation](./backup-api-endpoints.md)
- [BackupService.php](../app/Services/BackupService.php)
- [BackupController.php](../app/Http/Controllers/Admin/BackupController.php)
