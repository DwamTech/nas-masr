<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserPackages;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;



class PackagesController extends Controller
{
    /**
     * GET /api/user-packages?user_id=&active_only=1
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id'     => ['nullable', 'integer', 'exists:users,id'],
            'active_only' => ['nullable', 'boolean'],
        ]);

        $q = UserPackages::query()
            ->when(isset($data['user_id']), fn($qq) => $qq->where('user_id', $data['user_id']))
            ->when(isset($data['active_only']) && $data['active_only'], fn($qq) => $qq->active())
            ->orderByDesc('expire_date')
            ->orderByDesc('id');

        $rows = $q->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /api/user-packages/{package}
     */
    public function show(UserPackages $package): JsonResponse
    {
        return response()->json(['data' => $package]);
    }

    /**
     * POST /api/user-packages
     * body: user_id, featured_ads, standard_ads, days, start_date?, expire_date?
     */
    public function storeOrUpdate(Request $request): JsonResponse
    {
        $v = $request->validate([
            'user_id'            => ['required', 'integer', 'exists:users,id'],

            'categories'         => ['nullable', 'array'],
            'categories.*'       => ['integer', 'exists:categories,id'],

            'featured_ads'       => ['nullable', 'integer', 'min:0'],
            'standard_ads'       => ['nullable', 'integer', 'min:0'],

            'featured_days'      => ['nullable', 'integer', 'min:0'],
            'standard_days'      => ['nullable', 'integer', 'min:0'],

            // اختياري: إعادة تشغيل فوري لو عايز
            'start_featured_now' => ['nullable', 'boolean'],
            'start_standard_now' => ['nullable', 'boolean'],
        ]);

        $user = User::find($v['user_id']);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found ❌'], 404);
        }

        $pkg = UserPackages::firstOrNew(['user_id' => $user->id]);

        // Update categories if present
        if (array_key_exists('categories', $v)) {
            $pkg->categories = $v['categories'];
        }

        $touchedFeatured = array_key_exists('featured_ads', $v)
            || array_key_exists('featured_days', $v)
            || array_key_exists('start_featured_now', $v);

        $touchedStandard = array_key_exists('standard_ads', $v)
            || array_key_exists('standard_days', $v)
            || array_key_exists('start_standard_now', $v);

        if ($touchedFeatured) {
            $this->applyPlanUpdate(
                $pkg,
                'featured',
                $v['featured_ads']  ?? null,
                $v['featured_days'] ?? null,
                (bool)($v['start_featured_now'] ?? false)
            );
        }

        if ($touchedStandard) {
            $this->applyPlanUpdate(
                $pkg,
                'standard',
                $v['standard_ads']  ?? null,

                $v['standard_days'] ?? null,
                (bool)($v['start_standard_now'] ?? false)
            );
        }

        // ملحوظة: إحنا مش بنلمس الخطة التانية لو مش “touched”
        $pkg->save();

        return response()->json([
            'success' => true,
            'message' => 'Package assigned/updated successfully ✅',
            'data'    => $pkg->refresh(),
        ]);
    }

    /**
     * تحديث خطة واحدة (featured|standard) بمنطق واضح:
     * - لو ads==0 ⇒ Reset كامل للخطة.
     * - لو days اتغيرت، بنحدّثها بس من غير تغيير start/expire إلا لو startNow=true.
     * - Autostart تلقائي لو مفيش start_date قبل كده وكان فيه رصيد > 0.
     */
    protected function applyPlanUpdate(UserPackages $pkg, string $plan, ?int $ads, ?int $days, bool $startNow): void
    {
        $plan = strtolower($plan); // featured|standard

        $adsField    = "{$plan}_ads";
        $usedField   = "{$plan}_ads_used";
        $daysField   = "{$plan}_days";
        $startField  = "{$plan}_start_date";
        $expireField = "{$plan}_expire_date";

        // 1) تعديل عدد الإعلانات
        if ($ads !== null) {
            $ads = max(0, $ads);
            $pkg->{$adsField} = $ads;

            if ($ads === 0) {
                //Reset كامل للخطة
                $pkg->{$usedField}   = 0;
                $pkg->{$daysField}   = 0;
                $pkg->{$startField}  = null;
                $pkg->{$expireField} = null;
                return;
            }
        }

        // 2) تعديل عدد الأيام فقط
        if ($days !== null) {
            $pkg->{$daysField} = max(0, $days);
        }

        // 3) منطق التواريخ
        $hasStart   = !empty($pkg->{$startField});
        $daysVal    = (int)($pkg->{$daysField} ?? 0);
        $adsVal     = (int)($pkg->{$adsField} ?? 0);

        // Autostart: أول مرة يكون عنده رصيد إعلانات
        $shouldAutostart = !$hasStart && $adsVal > 0;

        if ($startNow || $shouldAutostart) {
            // تشغيل الآن أو تشغيل تلقائي لأول مرة
            $pkg->{$startField}  = now();
            $pkg->{$expireField} = $daysVal > 0
                ? now()->copy()->addDays($daysVal)
                : null;
        }
        // ⭐ NEW: لو الأيام اتعدلت والقطة شغالة بالفعل → اعادة حساب expire_date
        elseif ($hasStart && $days !== null) {
            $pkg->{$expireField} = $daysVal > 0
                ? Carbon::parse($pkg->{$startField})->addDays($daysVal)
                : null;
        }

        // ملاحظة: لو مفيش start ومفيش startNow ومفيش autostart  
        // يبقى بنسيب الخطة زي ما هي، ده المقصود.
    }






    /**
     * DELETE /api/user-packages/{package}
     */
    public function destroy(UserPackages $package): JsonResponse
    {
        $package->delete();
        return response()->json(null, 204);
    }

    /**
     * GET /api/users/{user}/packages/current
     */
    public function current(User $user): JsonResponse
    {
        $pkg = UserPackages::where('user_id', $user->id)
            ->active()
            ->orderByDesc('expire_date')
            ->orderByDesc('id')
            ->first();

        if (!$pkg) {
            return response()->json(['message' => 'No active package'], 404);
        }

        return response()->json(['data' => $pkg]);
    }

    /**
     * POST /api/users/{user}/packages/consume
     * body: type=featured|standard, qty=1
     * استهلاك ذرّي وآمن بدون سباقات
     */
    public function consume(User $user, Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['featured', 'standard'])],
            'qty'  => ['nullable', 'integer', 'min:1'],
        ]);
        $qty  = $data['qty'] ?? 1;

        // هات أحدث باقة نشطة
        $pkg = UserPackages::where('user_id', $user->id)
            ->active()
            ->orderByDesc('expire_date')
            ->orderByDesc('id')
            ->first();

        if (!$pkg) {
            return response()->json(['message' => 'No active package'], 422);
        }

        $ok = $this->atomicConsume($pkg, $data['type'], $qty);

        if (!$ok) {
            return response()->json(['message' => 'Not enough slots or package expired'], 422);
        }

        // رجّع الباقة بعد التحديث
        $pkg->refresh();
        return response()->json(['message' => 'Consumed', 'data' => $pkg]);
    }

    /**
     * POST /api/user-packages/{package}/start
     * body: days (اختياري: لو مش مبعوت بيستخدم days الحالية)
     */
    public function start(UserPackages $package, Request $request): JsonResponse
    {
        $data = $request->validate([
            'days' => ['nullable', 'integer', 'min:0'],
        ]);
        $days = (int)($data['days'] ?? $package->days ?? 0);
        $package->startNow($days);

        return response()->json(['message' => 'Package started', 'data' => $package->refresh()]);
    }

    /* =======================
     * Helpers
     * ======================= */

    /**
     * استهلاك ذرّي لعدد إعلانات من باقة معينة (featured|standard)
     */
    protected function atomicConsume(UserPackages $package, string $type, int $qty = 1): bool
    {
        $totalCol = $type === 'featured' ? 'featured_ads'      : 'standard_ads';
        $usedCol  = $type === 'featured' ? 'featured_ads_used' : 'standard_ads_used';
        $now      = now();

        $affected = UserPackages::whereKey($package->id)
            ->where(function ($q) use ($now) {
                $q->whereNull('expire_date')
                    ->orWhere('expire_date', '>=', $now);
            })
            ->whereRaw("$usedCol + ? <= $totalCol", [$qty])
            ->update([$usedCol => DB::raw("$usedCol + $qty")]);

        return $affected === 1;
    }
}
