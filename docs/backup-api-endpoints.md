# Backup System API Documentation

## Overview
This document describes the backup system API endpoints with the new features added.

## Authentication
All endpoints require admin authentication via `admin` middleware.

## Base URL
```
/api/admin/backups
```

---

## Endpoints

### 1. List Backups
**GET** `/api/admin/backups`

Returns a paginated list of all backup records.

**Response:**
```json
{
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "file_name": "backup_2026_03_31_143022_db.sql.gz",
        "type": "db",
        "status": "success",
        "size": 1048576,
        "size_formatted": "1.00 MB",
        "created_by": "Admin User",
        "created_at": "2026-03-31T14:30:22+00:00"
      }
    ],
    "per_page": 20,
    "total": 1
  }
}
```

---

### 2. Create Backup
**POST** `/api/admin/backups`

Creates a new database backup.

**Request Body:**
```json
{
  "type": "db"
}
```

**Response:**
```json
{
  "message": "تم إنشاء النسخة الاحتياطية بنجاح.",
  "data": {
    "id": 1,
    "file_name": "backup_2026_03_31_143022_db.sql.gz",
    "type": "db",
    "status": "success"
  }
}
```

---

### 3. Upload Backup ✨ NEW
**POST** `/api/admin/backups/upload`

Uploads an external backup file.

**Request:**
- Content-Type: `multipart/form-data`
- Field: `file` (required)
- Allowed types: `.gz`, `.sql`, `.zip`
- Max size: 500 MB

**Response:**
```json
{
  "message": "تم رفع النسخة الاحتياطية بنجاح.",
  "data": {
    "id": 2,
    "file_name": "backup_external_20260331143022.gz",
    "type": "upload",
    "size": 2097152,
    "size_formatted": "2.00 MB",
    "created_at": "2026-03-31T14:30:22+00:00"
  }
}
```

**Validation Errors:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "file": [
      "يجب اختيار ملف النسخة الاحتياطية."
    ]
  }
}
```

---

### 4. Download Backup ✨ NEW
**GET** `/api/admin/backups/{id}/download`

Downloads a backup file.

**Parameters:**
- `id` (path, required): Backup ID

**Response:**
- Binary file download
- Content-Disposition header with filename

**Errors:**
- `404`: Backup not found
- `500`: File not found on disk

---

### 5. Restore Backup
**POST** `/api/admin/backups/{id}/restore`

Restores a database from a backup.

**Parameters:**
- `id` (path, required): Backup ID

**Response:**
```json
{
  "message": "تمت استعادة قاعدة البيانات بنجاح.",
  "data": {
    "id": 1,
    "file_name": "backup_2026_03_31_143022_db.sql.gz",
    "type": "db"
  }
}
```

**Errors:**
- `404`: Backup not found
- `500`: Restore failed

---

### 6. Delete Backup
**DELETE** `/api/admin/backups/{id}`

Deletes a backup record and its file.

**Parameters:**
- `id` (path, required): Backup ID

**Response:**
```json
{
  "message": "تم حذف النسخة الاحتياطية بنجاح."
}
```

**Errors:**
- `404`: Backup not found
- `500`: Delete failed

---

### 7. Get History ✨ NEW
**GET** `/api/admin/backups/history`

Returns the last 50 backup operations history.

**Response:**
```json
{
  "data": [
    {
      "id": 3,
      "file_name": "backup_2026_03_31_143022_db.sql.gz",
      "type": "db",
      "status": "success",
      "size": 1048576,
      "size_formatted": "1.00 MB",
      "created_by": "Admin User",
      "created_at": "2026-03-31T14:30:22+00:00"
    },
    {
      "id": 2,
      "file_name": "backup_external.gz",
      "type": "upload",
      "status": "success",
      "size": 2097152,
      "size_formatted": "2.00 MB",
      "created_by": "Admin User",
      "created_at": "2026-03-31T14:00:00+00:00"
    }
  ]
}
```

---

### 8. Diagnostics
**GET** `/api/admin/backups/diagnostics`

Runs system diagnostics for the backup system.

**Response:**
```json
{
  "healthy": true,
  "report": [
    {
      "check": "mysqldump_binary",
      "status": "pass",
      "message": "mysqldump found at: /usr/bin/mysqldump"
    },
    {
      "check": "storage_writable",
      "status": "pass",
      "message": "Storage is writable"
    }
  ]
}
```

---

## Status Codes

- `200 OK`: Request successful
- `201 Created`: Resource created successfully
- `207 Multi-Status`: Diagnostics completed with some failures
- `400 Bad Request`: Invalid request
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Server error

---

## Backup Types

- `db`: Database backup created by the system
- `upload`: External backup uploaded by user

## Backup Status

- `pending`: Backup is being created
- `success`: Backup completed successfully
- `failed`: Backup failed

---

## Usage Examples

### Upload a backup using cURL
```bash
curl -X POST \
  http://your-domain.com/api/admin/backups/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/backup.sql.gz"
```

### Download a backup using cURL
```bash
curl -X GET \
  http://your-domain.com/api/admin/backups/1/download \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -o backup.sql.gz
```

### Get backup history
```bash
curl -X GET \
  http://your-domain.com/api/admin/backups/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

---

## Notes

1. All backup files are stored in `storage/app/backups/`
2. Uploaded files are automatically renamed with timestamp to avoid conflicts
3. The history endpoint returns the last 50 operations only
4. Download endpoint streams the file directly without loading it into memory
5. All operations are logged and tracked in the `backup_histories` table
