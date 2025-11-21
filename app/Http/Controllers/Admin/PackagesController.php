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
        $validated = $request->validate([
            'user_id'       => ['required', 'integer', 'exists:users,id'],
            'featured_ads'  => ['nullable', 'integer', 'min:0'],
            'standard_ads'  => ['nullable', 'integer', 'min:0'],
            'days'          => ['nullable', 'integer', 'min:1'],
        ]);

        $user = User::find($validated['user_id']);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found ❌',
            ], 404);
        }

        $package = UserPackages::where('user_id', $user->id)->orderByDesc('id')->first();

        $days       = $validated['days'] ?? 30;
        $expireDate = now()->addDays($days);

        if ($package) {
            // Update
            $package->update([
                'featured_ads' => $validated['featured_ads'] ?? $package->featured_ads,
                'standard_ads' => $validated['standard_ads'] ?? $package->standard_ads,
                'days'         => $days,
                'expire_date'  => $expireDate,
            ]);
        } else {
            $package = UserPackages::create([
                'user_id'      => $user->id,
                'featured_ads' => $validated['featured_ads'] ?? 0,
                'standard_ads' => $validated['standard_ads'] ?? 0,
                'days'         => $days,
                'start_date'   => now(),
                'expire_date'  => $expireDate,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Package assigned/updated successfully ✅',
            'data'    => $package->refresh(),
        ]);
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
