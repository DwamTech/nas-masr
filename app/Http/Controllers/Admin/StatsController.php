<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\SystemSetting;
use App\Models\Category;
use App\Models\CategoryField;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;

class StatsController extends Controller
{
  public function index(): JsonResponse
{
    $now = Carbon::now();

    $currentStart = $now->copy()->startOfMonth();
    $currentEnd   = $now->copy()->endOfMonth();

    $prevStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
    $prevEnd   = $now->copy()->subMonthNoOverflow()->endOfMonth();

    $totalAll         = Listing::query()->count();
    $totalAllCurrent  = Listing::query()->whereBetween('created_at', [$currentStart, $currentEnd])->count();
    $totalAllPrev     = Listing::query()->whereBetween('created_at', [$prevStart, $prevEnd])->count();

    $totalPending        = Listing::query()->where('status', 'Pending')->count();
    $totalPendingCurrent = Listing::query()
        ->where('status', 'Pending')
        ->whereBetween('created_at', [$currentStart, $currentEnd])
        ->count();
    $totalPendingPrev    = Listing::query()
        ->where('status', 'Pending')
        ->whereBetween('created_at', [$prevStart, $prevEnd])
        ->count();

    $totalRejected        = Listing::query()->where('status', 'Rejected')->count();
    $totalRejectedCurrent = Listing::query()
        ->where('status', 'Rejected')
        ->whereBetween('created_at', [$currentStart, $currentEnd])
        ->count();
    $totalRejectedPrev    = Listing::query()
        ->where('status', 'Rejected')
        ->whereBetween('created_at', [$prevStart, $prevEnd])
        ->count();

    $totalActive        = Listing::query()->active()->count();
    $totalActiveCurrent = Listing::query()->active()
        ->whereBetween('created_at', [$currentStart, $currentEnd])
        ->count();
    $totalActivePrev    = Listing::query()->active()
        ->whereBetween('created_at', [$prevStart, $prevEnd])
        ->count();

    $makeStat = function (int $total, int $current, int $prev): array {
        if ($prev === 0) {
            $percent = $current > 0 ? 100.0 : 0.0;
        } else {
            $percent = round((($current - $prev) / $prev) * 100, 2);
        }

        return [
            'count'     => $total,
            'percent'   => $percent,             // 8.5 مثلاً
            'direction' => $percent >= 0 ? 'up' : 'down', // علشان الفرونت يحط علامة + أو سهم
        ];
    };

    return response()->json([
        'cards' => [
            'rejected' => $makeStat($totalRejected, $totalRejectedCurrent, $totalRejectedPrev),
            'pending'  => $makeStat($totalPending,  $totalPendingCurrent,  $totalPendingPrev),
            'active'   => $makeStat($totalActive,   $totalActiveCurrent,   $totalActivePrev),
            'total'    => $makeStat($totalAll,      $totalAllCurrent,      $totalAllPrev),
        ],
        'periods' => [
            'current_month' => [
                'start' => $currentStart->toDateString(),
                'end'   => $currentEnd->toDateString(),
            ],
            'previous_month' => [
                'start' => $prevStart->toDateString(),
                'end'   => $prevEnd->toDateString(),
            ],
        ],
    ]);
}
    public function recentActivities(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        Carbon::setLocale('ar');

        // Listings recent updates
        $listings = Listing::query()
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function (Listing $l) {
                $type = 'listing_updated';
                $message = 'تم تحديث إعلان';

                if ($l->status === 'Rejected') {
                    $type = 'listing_rejected';
                    $message = 'تم رفض إعلان';
                } elseif ($l->status === 'Pending') {
                    $type = 'listing_pending';
                    $message = 'تم تعليق إعلان';
                } elseif ($l->admin_approved) {
                    $type = 'listing_approved';
                    $message = 'تم تفعيل إعلان';
                } else {
                    $type = 'listing_disabled';
                    $message = 'تم تعطيل إعلان';
                }

                $ts = Carbon::parse($l->updated_at);
                return [
                    'type' => $type,
                    'message' => $message,
                    'entity' => 'listing',
                    'id' => $l->id,
                    'status' => $l->status,
                    'admin_approved' => (bool) $l->admin_approved,
                    'timestamp' => $ts->toIso8601String(),
                    'ago' => $ts->diffForHumans(),
                ];
            });

        // System settings updates
        $settings = SystemSetting::query()
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function (SystemSetting $s) {
                $ts = Carbon::parse($s->updated_at);
                return [
                    'type' => 'settings_updated',
                    'message' => 'تم تحديث الإعدادات',
                    'entity' => 'system_settings',
                    'id' => $s->id,
                    'timestamp' => $ts->toIso8601String(),
                    'ago' => $ts->diffForHumans(),
                ];
            });

        // Optional: category and category-fields updates in admin panel
        $categories = Category::query()
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function (Category $c) {
                $ts = Carbon::parse($c->updated_at);
                return [
                    'type' => 'category_updated',
                    'message' => 'تم تحديث قسم',
                    'entity' => 'category',
                    'id' => $c->id,
                    'timestamp' => $ts->toIso8601String(),
                    'ago' => $ts->diffForHumans(),
                ];
            });

        $categoryFields = CategoryField::query()
            ->orderBy('updated_at', 'desc')
            ->take($limit)
            ->get()
            ->map(function (CategoryField $f) {
                $ts = Carbon::parse($f->updated_at);
                return [
                    'type' => 'category_field_updated',
                    'message' => 'تم تحديث حقل قسم',
                    'entity' => 'category_field',
                    'id' => $f->id,
                    'timestamp' => $ts->toIso8601String(),
                    'ago' => $ts->diffForHumans(),
                ];
            });

        $activities = collect()
            ->merge($listings)
            ->merge($settings)
            ->merge($categories)
            ->merge($categoryFields)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return response()->json([
            'count' => $activities->count(),
            'activities' => $activities,
        ]);
    }

    
    public function usersSummary(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $role = $request->query('role'); // admin, user, reviewer, advertiser
        $status = $request->query('status'); // active, blocked
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when($role, fn($qr) => $qr->where('role', $role))
            ->when($status, fn($qr) => $qr->where('status', $status))
            ->when($q !== '', function ($qr) use ($q) {
                $qr->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%$q%")
                        ->orWhere('phone', 'like', "%$q%")
                        ->orWhere('referral_code', 'like', "%$q%");
                });
            })
            ->withCount('listings')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $data = collect($users->items())->map(function (User $u) {
            $userCode = $u->referral_code ?: (string) $u->id;
            return [
                'id' => $u->id,
                'name' => $u->name,
                'phone' => $u->phone,
                'user_code' => $userCode,
                'status' => $u->status ?? 'active',
                'registered_at' => optional($u->created_at)->toDateString(),
                'listings_count' => $u->listings_count ?? 0,
                'role' => $u->role ?? 'user',
            ];
        })->values();

        return response()->json([
            'meta' => [
                'page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
            'users' => $data,
        ]);
    }
}