/**
 * Backup System Frontend Integration Examples
 * 
 * These examples show how to integrate the new backup features
 * into your frontend application (React, Vue, or vanilla JS)
 */

// ============================================
// Configuration
// ============================================

const API_BASE_URL = '/api/admin/backups';
const AUTH_TOKEN = 'your-auth-token-here'; // Get from your auth system

// ============================================
// Helper Functions
// ============================================

/**
 * Make authenticated API request
 */
async function apiRequest(endpoint, options = {}) {
  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers: {
      'Authorization': `Bearer ${AUTH_TOKEN}`,
      'Accept': 'application/json',
      ...options.headers,
    },
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Request failed');
  }

  return response;
}

// ============================================
// 1. List Backups
// ============================================

async function listBackups(page = 1) {
  const response = await apiRequest(`?page=${page}`);
  const data = await response.json();
  return data.data;
}

// Usage:
// const backups = await listBackups(1);
// console.log(backups);

// ============================================
// 2. Create Backup
// ============================================

async function createBackup(type = 'db') {
  const response = await apiRequest('', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ type }),
  });

  const data = await response.json();
  return data;
}

// Usage:
// const result = await createBackup('db');
// console.log(result.message); // "تم إنشاء النسخة الاحتياطية بنجاح."

// ============================================
// 3. Upload Backup ✨ NEW
// ============================================

async function uploadBackup(file, onProgress = null) {
  const formData = new FormData();
  formData.append('file', file);

  // Create XMLHttpRequest for progress tracking
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();

    // Track upload progress
    if (onProgress) {
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percentComplete = (e.loaded / e.total) * 100;
          onProgress(percentComplete);
        }
      });
    }

    xhr.addEventListener('load', () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve(JSON.parse(xhr.responseText));
      } else {
        reject(new Error(JSON.parse(xhr.responseText).message));
      }
    });

    xhr.addEventListener('error', () => {
      reject(new Error('Upload failed'));
    });

    xhr.open('POST', `${API_BASE_URL}/upload`);
    xhr.setRequestHeader('Authorization', `Bearer ${AUTH_TOKEN}`);
    xhr.send(formData);
  });
}

// Usage with progress:
// const file = document.getElementById('fileInput').files[0];
// const result = await uploadBackup(file, (progress) => {
//   console.log(`Upload progress: ${progress.toFixed(2)}%`);
// });
// console.log(result.message);

// ============================================
// 4. Download Backup ✨ NEW
// ============================================

async function downloadBackup(backupId, fileName = 'backup.sql.gz') {
  const response = await apiRequest(`/${backupId}/download`);
  const blob = await response.blob();

  // Create download link
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.style.display = 'none';
  a.href = url;
  a.download = fileName;
  document.body.appendChild(a);
  a.click();
  
  // Cleanup
  window.URL.revokeObjectURL(url);
  document.body.removeChild(a);
}

// Usage:
// await downloadBackup(1, 'my_backup.sql.gz');

// ============================================
// 5. Get Backup History ✨ NEW
// ============================================

async function getBackupHistory() {
  const response = await apiRequest('/history');
  const data = await response.json();
  return data.data;
}

// Usage:
// const history = await getBackupHistory();
// console.log(history); // Last 50 operations

// ============================================
// 6. Restore Backup
// ============================================

async function restoreBackup(backupId) {
  const confirmed = confirm(
    'هل أنت متأكد من استعادة هذه النسخة الاحتياطية؟ سيتم استبدال البيانات الحالية.'
  );

  if (!confirmed) {
    return null;
  }

  const response = await apiRequest(`/${backupId}/restore`, {
    method: 'POST',
  });

  const data = await response.json();
  return data;
}

// Usage:
// const result = await restoreBackup(1);
// if (result) {
//   console.log(result.message);
// }

// ============================================
// 7. Delete Backup
// ============================================

async function deleteBackup(backupId) {
  const confirmed = confirm('هل أنت متأكد من حذف هذه النسخة الاحتياطية؟');

  if (!confirmed) {
    return null;
  }

  const response = await apiRequest(`/${backupId}`, {
    method: 'DELETE',
  });

  const data = await response.json();
  return data;
}

// Usage:
// const result = await deleteBackup(1);
// if (result) {
//   console.log(result.message);
// }

// ============================================
// 8. Get Diagnostics
// ============================================

async function getDiagnostics() {
  const response = await apiRequest('/diagnostics');
  const data = await response.json();
  return data;
}

// Usage:
// const diagnostics = await getDiagnostics();
// console.log('System healthy:', diagnostics.healthy);
// console.log('Report:', diagnostics.report);

// ============================================
// React Component Example
// ============================================

/*
import React, { useState } from 'react';

function BackupUploader() {
  const [file, setFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);
  const [message, setMessage] = useState('');

  const handleFileChange = (e) => {
    setFile(e.target.files[0]);
    setMessage('');
  };

  const handleUpload = async () => {
    if (!file) {
      setMessage('الرجاء اختيار ملف');
      return;
    }

    setUploading(true);
    setProgress(0);

    try {
      const result = await uploadBackup(file, (p) => {
        setProgress(p);
      });
      
      setMessage(result.message);
      setFile(null);
    } catch (error) {
      setMessage('خطأ: ' + error.message);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div>
      <h2>رفع نسخة احتياطية</h2>
      
      <input
        type="file"
        accept=".gz,.sql,.zip"
        onChange={handleFileChange}
        disabled={uploading}
      />
      
      <button onClick={handleUpload} disabled={uploading || !file}>
        {uploading ? 'جاري الرفع...' : 'رفع'}
      </button>

      {uploading && (
        <div>
          <progress value={progress} max="100" />
          <span>{progress.toFixed(0)}%</span>
        </div>
      )}

      {message && <p>{message}</p>}
    </div>
  );
}

export default BackupUploader;
*/

// ============================================
// Vue Component Example
// ============================================

/*
<template>
  <div class="backup-uploader">
    <h2>رفع نسخة احتياطية</h2>
    
    <input
      type="file"
      accept=".gz,.sql,.zip"
      @change="handleFileChange"
      :disabled="uploading"
    />
    
    <button @click="handleUpload" :disabled="uploading || !file">
      {{ uploading ? 'جاري الرفع...' : 'رفع' }}
    </button>

    <div v-if="uploading">
      <progress :value="progress" max="100"></progress>
      <span>{{ progress.toFixed(0) }}%</span>
    </div>

    <p v-if="message">{{ message }}</p>
  </div>
</template>

<script>
export default {
  data() {
    return {
      file: null,
      uploading: false,
      progress: 0,
      message: '',
    };
  },
  methods: {
    handleFileChange(e) {
      this.file = e.target.files[0];
      this.message = '';
    },
    async handleUpload() {
      if (!this.file) {
        this.message = 'الرجاء اختيار ملف';
        return;
      }

      this.uploading = true;
      this.progress = 0;

      try {
        const result = await uploadBackup(this.file, (p) => {
          this.progress = p;
        });
        
        this.message = result.message;
        this.file = null;
      } catch (error) {
        this.message = 'خطأ: ' + error.message;
      } finally {
        this.uploading = false;
      }
    },
  },
};
</script>
*/

// ============================================
// Complete Backup Manager Example
// ============================================

class BackupManager {
  constructor(apiBaseUrl, authToken) {
    this.apiBaseUrl = apiBaseUrl;
    this.authToken = authToken;
  }

  async request(endpoint, options = {}) {
    const response = await fetch(`${this.apiBaseUrl}${endpoint}`, {
      ...options,
      headers: {
        'Authorization': `Bearer ${this.authToken}`,
        'Accept': 'application/json',
        ...options.headers,
      },
    });

    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Request failed');
    }

    return response;
  }

  async list(page = 1) {
    const response = await this.request(`?page=${page}`);
    return await response.json();
  }

  async create(type = 'db') {
    const response = await this.request('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ type }),
    });
    return await response.json();
  }

  async upload(file, onProgress = null) {
    const formData = new FormData();
    formData.append('file', file);

    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();

      if (onProgress) {
        xhr.upload.addEventListener('progress', (e) => {
          if (e.lengthComputable) {
            onProgress((e.loaded / e.total) * 100);
          }
        });
      }

      xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          resolve(JSON.parse(xhr.responseText));
        } else {
          reject(new Error(JSON.parse(xhr.responseText).message));
        }
      });

      xhr.addEventListener('error', () => reject(new Error('Upload failed')));

      xhr.open('POST', `${this.apiBaseUrl}/upload`);
      xhr.setRequestHeader('Authorization', `Bearer ${this.authToken}`);
      xhr.send(formData);
    });
  }

  async download(backupId, fileName = 'backup.sql.gz') {
    const response = await this.request(`/${backupId}/download`);
    const blob = await response.blob();

    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  }

  async restore(backupId) {
    const response = await this.request(`/${backupId}/restore`, {
      method: 'POST',
    });
    return await response.json();
  }

  async delete(backupId) {
    const response = await this.request(`/${backupId}`, {
      method: 'DELETE',
    });
    return await response.json();
  }

  async history() {
    const response = await this.request('/history');
    const data = await response.json();
    return data.data;
  }

  async diagnostics() {
    const response = await this.request('/diagnostics');
    return await response.json();
  }
}

// Usage:
// const backupManager = new BackupManager(API_BASE_URL, AUTH_TOKEN);
// const backups = await backupManager.list();
// await backupManager.upload(file, (progress) => console.log(progress));
// await backupManager.download(1, 'my_backup.sql.gz');

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    listBackups,
    createBackup,
    uploadBackup,
    downloadBackup,
    getBackupHistory,
    restoreBackup,
    deleteBackup,
    getDiagnostics,
    BackupManager,
  };
}
