# أوامر تشغيل نظام المناديب الجديد

## 1. تشغيل الـ Migration (إضافة Unique Constraint)

```bash
php artisan migrate
```

**ماذا يفعل:**
- يضيف unique constraint على `user_clients.user_id`
- يمنع تكرار سجلات المناديب في الجدول

---

## 2. اختبار النظام

```bash
php test_delegates_system.php
```

**ماذا يفعل:**
- يعرض المناديب الموجودين في قاعدة البيانات
- يعرض المستخدمين الذين لديهم referral_code
- يتحقق من صحة بنية جدول user_clients
- يعرض إحصائيات عامة

---

## 3. إنشاء مندوب تجريبي (اختياري)

```bash
php artisan tinker
```

ثم في Tinker:

```php
$user = \App\Models\User::find(5); // استبدل 5 بـ ID مستخدم موجود
$user->role = 'representative';
$user->save();

$userClient = \App\Models\UserClient::firstOrCreate(
    ['user_id' => $user->id],
    ['clients' => []]
);

echo "Delegate created! Code: " . $user->id;
exit;
```

---

## 4. إضافة عميل يدوياً لمندوب (اختياري)

```bash
php artisan tinker
```

ثم في Tinker:

```php
// معرف المندوب
$delegateId = 5;

// معرف العميل
$clientId = 10;

$userClient = \App\Models\UserClient::where('user_id', $delegateId)->first();
$clients = $userClient->clients ?? [];
$clients[] = $clientId;
$userClient->clients = $clients;
$userClient->save();

// تحديث العميل
$client = \App\Models\User::find($clientId);
$client->referral_code = $delegateId;
$client->save();

echo "Client $clientId added to delegate $delegateId";
exit;
```

---

## 5. عرض جميع المناديب

```bash
php artisan tinker
```

ثم في Tinker:

```php
$reps = \App\Models\User::where('role', 'representative')->get();

foreach ($reps as $rep) {
    $uc = \App\Models\UserClient::where('user_id', $rep->id)->first();
    $count = count($uc->clients ?? []);
    echo "ID: {$rep->id} | Name: {$rep->name} | Clients: $count\n";
}

exit;
```

---

## 6. تنظيف البيانات القديمة (إذا لزم الأمر)

⚠️ **تحذير: هذا الأمر سيحذف البيانات المكررة في user_clients**

```bash
php artisan tinker
```

ثم في Tinker:

```php
// البحث عن السجلات المكررة
$duplicates = \App\Models\UserClient::select('user_id')
    ->groupBy('user_id')
    ->havingRaw('COUNT(*) > 1')
    ->pluck('user_id');

foreach ($duplicates as $userId) {
    $records = \App\Models\UserClient::where('user_id', $userId)->get();
    
    // الاحتفاظ بأول سجل فقط
    $keep = $records->first();
    
    // دمج جميع الـ clients
    $allClients = [];
    foreach ($records as $rec) {
        $allClients = array_merge($allClients, $rec->clients ?? []);
    }
    $allClients = array_values(array_unique($allClients));
    
    $keep->clients = $allClients;
    $keep->save();
    
    // حذف السجلات الأخرى
    $records->where('id', '!=', $keep->id)->each(function($rec) {
        $rec->delete();
    });
}

echo "Cleanup completed!";
exit;
```

---

## 7. فحص صحة البيانات

```bash
php artisan tinker
```

ثم في Tinker:

```php
// التحقق من أن جميع المستخدمين بـ referral_code لديهم مندوب صحيح
$users = \App\Models\User::whereNotNull('referral_code')->get();

$errors = 0;
foreach ($users as $user) {
    $delegate = \App\Models\User::where('id', $user->referral_code)
        ->where('role', 'representative')
        ->first();
    
    if (!$delegate) {
        echo "❌ User {$user->id} has invalid referral_code: {$user->referral_code}\n";
        $errors++;
    }
}

if ($errors === 0) {
    echo "✅ All referral codes are valid!\n";
} else {
    echo "⚠️ Found $errors invalid referral codes\n";
}

exit;
```

---

## ملاحظات مهمة

1. **قبل تشغيل Migration:**
   - تأكد من عمل backup لقاعدة البيانات
   - تحقق من عدم وجود سجلات مكررة في `user_clients`

2. **بعد تشغيل Migration:**
   - شغل سكريبت الاختبار للتأكد من صحة البيانات
   - اختبر API endpoints المحدثة

3. **إذا واجهت مشكلة في Migration:**
   - نظف السجلات المكررة أولاً (الأمر رقم 6)
   - ثم شغل Migration مرة أخرى
