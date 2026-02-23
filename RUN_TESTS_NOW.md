# 🚀 تشغيل الاختبارات الآن

## خطوة واحدة فقط!

افتح PowerShell في مجلد المشروع وشغّل:

```powershell
php artisan test --filter=ListingCreationWithFreePlanTest
```

## أو استخدم الـ script:

```powershell
.\run_free_plan_tests.ps1
```

---

## النتيجة المتوقعة ✅

```
PASS  Tests\Feature\ListingCreationWithFreePlanTest
✓ ad accepted when plan price is zero
✓ featured ad accepted when price is zero
✓ payment required when price not zero and no package
✓ ad accepted when user has package balance
✓ payment required when package balance is zero
✓ admin can create ad without restrictions

Tests:  6 passed
```

---

## إذا ظهرت أخطاء ❌

راجع ملف `TEST_INSTRUCTIONS.md` للحلول

---

**جاهز؟ شغّل الأمر الآن! 🎯**
