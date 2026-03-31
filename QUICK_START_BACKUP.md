# 🚀 Quick Start Guide - Backup System

## البدء السريع

دليل سريع لاستخدام الوظائف الجديدة في نظام النسخ الاحتياطي.

---

## 📋 المتطلبات

- ✅ Laravel application running
- ✅ Admin authentication configured
- ✅ Database configured
- ✅ Storage configured

---

## 🎯 الوظائف الجديدة

### 1. رفع نسخة احتياطية (Upload)

#### من Postman/Insomnia:
```
POST http://localhost/api/admin/backups/upload
Headers:
  Authorization: Bearer YOUR_TOKEN
Body:
  form-data
  file: [select your .gz/.sql/.zip file]
```

#### من cURL:
```bash
curl -X POST http://localhost/api/admin/backups/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/backup.sql.gz"
```

#### Response:
```json
{
  "message": "تم رفع النسخة الاحتياطية بنجاح.",
  "data": {
    "id": 1,
    "file_name": "backup_20260331143022.sql.gz",
    "type": "upload",
    "size": 1048576,
    "size_formatted": "1.00 MB",
    "created_at": "2026-03-31T14:30:22+00:00"
  }
}
```

---

### 2. تحميل نسخة احتياطية (Download)

#### من Postman/Insomnia:
```
GET http://localhost/api/admin/backups/1/download
Headers:
  Authorization: Bearer YOUR_TOKEN
```

#### من cURL:
```bash
curl -X GET http://localhost/api/admin/backups/1/download \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o backup.sql.gz
```

#### من المتصفح:
```
http://localhost/api/admin/backups/1/download?token=YOUR_TOKEN
```

---

### 3. عرض السجل (History)

#### من Postman/Insomnia:
```
GET http://localhost/api/admin/backups/history
Headers:
  Authorization: Bearer YOUR_TOKEN
```

#### من cURL:
```bash
curl -X GET http://localhost/api/admin/backups/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### Response:
```json
{
  "data": [
    {
      "id": 3,
      "file_name": "backup_20260331143022.sql.gz",
      "type": "db",
      "status": "success",
      "size": 1048576,
      "size_formatted": "1.00 MB",
      "created_by": "Admin User",
      "created_at": "2026-03-31T14:30:22+00:00"
    }
  ]
}
```

---

## 🧪 اختبار الوظائف

### 1. إنشاء ملف اختبار
```bash
# إنشاء ملف SQL بسيط
echo "SELECT 1;" > test_backup.sql

# ضغطه
gzip test_backup.sql

# الآن لديك test_backup.sql.gz
```

### 2. رفع الملف
```bash
curl -X POST http://localhost/api/admin/backups/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@test_backup.sql.gz"
```

### 3. عرض القائمة
```bash
curl -X GET http://localhost/api/admin/backups \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. تحميل الملف
```bash
curl -X GET http://localhost/api/admin/backups/1/download \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o downloaded_backup.sql.gz
```

### 5. عرض السجل
```bash
curl -X GET http://localhost/api/admin/backups/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## 💻 Frontend Integration

### HTML + JavaScript Example

```html
<!DOCTYPE html>
<html>
<head>
    <title>Backup Manager</title>
</head>
<body>
    <h1>Backup Manager</h1>
    
    <!-- Upload Form -->
    <h2>Upload Backup</h2>
    <input type="file" id="fileInput" accept=".gz,.sql,.zip">
    <button onclick="uploadBackup()">Upload</button>
    <div id="uploadProgress"></div>
    <div id="uploadMessage"></div>
    
    <!-- History -->
    <h2>Backup History</h2>
    <button onclick="loadHistory()">Load History</button>
    <div id="historyList"></div>

    <script>
        const API_URL = '/api/admin/backups';
        const TOKEN = 'YOUR_AUTH_TOKEN'; // Get from your auth system

        async function uploadBackup() {
            const fileInput = document.getElementById('fileInput');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('Please select a file');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);

            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    document.getElementById('uploadProgress').textContent = 
                        `Progress: ${percent.toFixed(0)}%`;
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status === 201) {
                    const response = JSON.parse(xhr.responseText);
                    document.getElementById('uploadMessage').textContent = 
                        response.message;
                    loadHistory(); // Refresh history
                } else {
                    const error = JSON.parse(xhr.responseText);
                    document.getElementById('uploadMessage').textContent = 
                        'Error: ' + error.message;
                }
            });

            xhr.open('POST', `${API_URL}/upload`);
            xhr.setRequestHeader('Authorization', `Bearer ${TOKEN}`);
            xhr.send(formData);
        }

        async function loadHistory() {
            const response = await fetch(`${API_URL}/history`, {
                headers: {
                    'Authorization': `Bearer ${TOKEN}`
                }
            });

            const data = await response.json();
            const historyList = document.getElementById('historyList');
            
            historyList.innerHTML = '<ul>' + 
                data.data.map(backup => `
                    <li>
                        ${backup.file_name} 
                        (${backup.size_formatted}) 
                        - ${backup.status}
                        <button onclick="downloadBackup(${backup.id}, '${backup.file_name}')">
                            Download
                        </button>
                    </li>
                `).join('') + 
            '</ul>';
        }

        async function downloadBackup(id, fileName) {
            const response = await fetch(`${API_URL}/${id}/download`, {
                headers: {
                    'Authorization': `Bearer ${TOKEN}`
                }
            });

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            a.click();
            window.URL.revokeObjectURL(url);
        }

        // Load history on page load
        loadHistory();
    </script>
</body>
</html>
```

---

## 🔧 Troubleshooting

### مشكلة: "File is required"
**الحل:** تأكد من إرسال الملف في field اسمه `file`

### مشكلة: "File must be a zip archive"
**الحل:** تأكد من أن الملف بامتداد `.gz`, `.sql`, أو `.zip`

### مشكلة: "File size exceeds maximum"
**الحل:** حجم الملف يجب ألا يتجاوز 500 ميجابايت

### مشكلة: "Unauthorized"
**الحل:** تأكد من إرسال Authorization header صحيح

### مشكلة: "Forbidden"
**الحل:** تأكد من أن المستخدم لديه صلاحيات admin

---

## 📝 Validation Rules

### Upload File Validation
- **Required:** نعم
- **Type:** file
- **Allowed Extensions:** `.gz`, `.sql`, `.zip`
- **Max Size:** 500 MB (512000 KB)

### Error Messages (Arabic)
- `file.required`: "يجب اختيار ملف النسخة الاحتياطية."
- `file.file`: "الملف المرفوع غير صالح."
- `file.mimes`: "يجب أن يكون الملف من نوع: gz, sql, zip."
- `file.max`: "حجم الملف يجب ألا يتجاوز 500 ميجابايت."

---

## 🎯 Use Cases

### Use Case 1: Backup Before Update
```bash
# 1. Create backup
curl -X POST http://localhost/api/admin/backups \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"type":"db"}'

# 2. Download it for safety
curl -X GET http://localhost/api/admin/backups/1/download \
  -H "Authorization: Bearer TOKEN" \
  -o pre_update_backup.sql.gz

# 3. Perform your update
# ...

# 4. If something goes wrong, restore
curl -X POST http://localhost/api/admin/backups/1/restore \
  -H "Authorization: Bearer TOKEN"
```

### Use Case 2: Migrate from Another Server
```bash
# 1. On old server: Create and download backup
curl -X POST http://old-server/api/admin/backups \
  -H "Authorization: Bearer TOKEN" \
  -d '{"type":"db"}'

curl -X GET http://old-server/api/admin/backups/1/download \
  -H "Authorization: Bearer TOKEN" \
  -o migration_backup.sql.gz

# 2. On new server: Upload and restore
curl -X POST http://new-server/api/admin/backups/upload \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@migration_backup.sql.gz"

curl -X POST http://new-server/api/admin/backups/1/restore \
  -H "Authorization: Bearer TOKEN"
```

### Use Case 3: Regular Backup Monitoring
```bash
# Check backup history daily
curl -X GET http://localhost/api/admin/backups/history \
  -H "Authorization: Bearer TOKEN" \
  | jq '.data[] | select(.created_at > "2026-03-30")'
```

---

## 📞 Need Help?

راجع التوثيق الكامل:
- **API Docs:** `docs/backup-api-endpoints.md`
- **Updates:** `docs/BACKUP_SYSTEM_UPDATES.md`
- **Frontend Examples:** `docs/backup-frontend-examples.js`
- **Summary:** `BACKUP_IMPLEMENTATION_SUMMARY.md`

---

## ✅ Checklist للبدء

- [ ] تأكد من أن Laravel يعمل
- [ ] تأكد من أن Authentication يعمل
- [ ] احصل على Admin token
- [ ] جرب Upload endpoint
- [ ] جرب Download endpoint
- [ ] جرب History endpoint
- [ ] اختبر من Frontend
- [ ] راجع التوثيق الكامل

**جاهز للاستخدام! 🚀**
