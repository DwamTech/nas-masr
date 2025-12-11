<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use App\Models\UserPlanSubscription;
use App\Support\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserSubscriptionsController extends Controller
{
    /**
     * GET /api/admin/user-subscriptions
     * عرض كل اشتراكات المستخدمين
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'plan_type' => ['nullable', 'string', Rule::in(['featured', 'standard'])],
            'active_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $perPage = $data['per_page'] ?? 20;

        $query = UserPlanSubscription::query()
            ->with(['user:id,name,phone', 'category:id,name,slug'])
            ->when(isset($data['user_id']), fn($q) => $q->where('user_id', $data['user_id']))
            ->when(isset($data['category_id']), fn($q) => $q->where('category_id', $data['category_id']))
            ->when(isset($data['plan_type']), fn($q) => $q->where('plan_type', $data['plan_type']))
            ->when(isset($data['active_only']) && $data['active_only'], function ($q) {
                $q->where(function ($qq) {
                    $qq->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                })->whereRaw('ads_used < ads_total');
            })
            ->orderByDesc('id');

        $subs = $query->paginate($perPage);

        $items = collect($subs->items())->map(function (UserPlanSubscription $s) {
            return [
                'id' => $s->id,
                'user_id' => $s->user_id,
                'user_name' => $s->user?->name,
                'user_phone' => $s->user?->phone,
                'category_id' => $s->category_id,
                'category_name' => $s->category?->name,
                'category_slug' => $s->category?->slug,
                'plan_type' => $s->plan_type,
                'days' => (int) $s->days,
                'subscribed_at' => $s->subscribed_at,
                'expires_at' => $s->expires_at,
                'ads_total' => (int) $s->ads_total,
                'ads_used' => (int) $s->ads_used,
                'remaining' => (int) $s->ads_remaining,
                'price' => (float) $s->price,
                'payment_status' => $s->payment_status,
                'active' => $s->is_active,
                'created_at' => $s->created_at,
            ];
        });

        return response()->json([
            'meta' => [
                'page' => $subs->currentPage(),
                'per_page' => $subs->perPage(),
                'total' => $subs->total(),
                'last_page' => $subs->lastPage(),
            ],
            'items' => $items,
        ]);
    }

    /**
     * GET /api/admin/user-subscriptions/{id}
     * تفاصيل اشتراك معين
     */
    public function show(int $id): JsonResponse
    {
        $sub = UserPlanSubscription::with(['user', 'category'])->findOrFail($id);

        return response()->json([
            'subscription' => [
                'id' => $sub->id,
                'user_id' => $sub->user_id,
                'user_name' => $sub->user?->name,
                'user_phone' => $sub->user?->phone,
                'category_id' => $sub->category_id,
                'category_name' => $sub->category?->name,
                'category_slug' => $sub->category?->slug,
                'plan_type' => $sub->plan_type,
                'days' => (int) $sub->days,
                'subscribed_at' => $sub->subscribed_at,
                'expires_at' => $sub->expires_at,
                'ads_total' => (int) $sub->ads_total,
                'ads_used' => (int) $sub->ads_used,
                'remaining' => (int) $sub->ads_remaining,
                'price' => (float) $sub->price,
                'ad_price' => (float) $sub->ad_price,
                'payment_status' => $sub->payment_status,
                'payment_method' => $sub->payment_method,
                'payment_reference' => $sub->payment_reference,
                'active' => $sub->is_active,
                'created_at' => $sub->created_at,
                'updated_at' => $sub->updated_at,
            ],
        ]);
    }

    /**
     * POST /api/admin/user-subscriptions
     * إنشاء اشتراك جديد لمستخدم في قسم معين
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'category_id' => ['required_without:category_slug', 'nullable', 'integer', 'exists:categories,id'],
            'category_slug' => ['required_without:category_id', 'nullable', 'string'],
            'plan_type' => ['required', 'string', Rule::in(['featured', 'standard'])],
            'ads_total' => ['required', 'integer', 'min:0'],
            'days' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'ad_price' => ['nullable', 'numeric', 'min:0'],
            'start_now' => ['nullable', 'boolean'],
        ]);

        // تحويل category_slug إلى category_id
        if (empty($data['category_id']) && !empty($data['category_slug'])) {
            $sec = Section::fromSlug($data['category_slug']);
            $data['category_id'] = $sec->id();
        }

        $days = $data['days'] ?? 0;
        $startNow = $data['start_now'] ?? true;

        $sub = UserPlanSubscription::updateOrCreate(
            [
                'user_id' => $data['user_id'],
                'category_id' => $data['category_id'],
                'plan_type' => $data['plan_type'],
            ],
            [
                'ads_total' => $data['ads_total'],
                'ads_used' => 0,
                'days' => $days,
                'subscribed_at' => $startNow ? now() : null,
                'expires_at' => $startNow && $days > 0 ? now()->addDays($days) : null,
                'price' => $data['price'] ?? 0,
                'ad_price' => $data['ad_price'] ?? 0,
                'payment_status' => 'admin_assigned',
                'payment_method' => 'admin',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء الاشتراك بنجاح ✅',
            'subscription' => $sub->refresh(),
        ], 201);
    }

    /**
     * PATCH /api/admin/user-subscriptions/{id}
     * تعديل اشتراك (عدد الإعلانات، الأيام، إلخ)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $sub = UserPlanSubscription::findOrFail($id);

        $data = $request->validate([
            'ads_total' => ['nullable', 'integer', 'min:0'],
            'ads_used' => ['nullable', 'integer', 'min:0'],
            'days' => ['nullable', 'integer', 'min:0'],
            'expires_at' => ['nullable', 'date'],
            'restart' => ['nullable', 'boolean'],  // إعادة تشغيل الاشتراك من الآن
        ]);

        if (isset($data['ads_total'])) {
            $sub->ads_total = $data['ads_total'];
        }

        if (isset($data['ads_used'])) {
            $sub->ads_used = $data['ads_used'];
        }

        if (isset($data['days'])) {
            $sub->days = $data['days'];
        }

        // إعادة تشغيل الاشتراك
        if (!empty($data['restart'])) {
            $sub->subscribed_at = now();
            $days = $data['days'] ?? $sub->days;
            $sub->expires_at = $days > 0 ? now()->addDays($days) : null;
        } elseif (isset($data['expires_at'])) {
            $sub->expires_at = $data['expires_at'];
        }

        $sub->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الاشتراك بنجاح ✅',
            'subscription' => $sub->refresh(),
        ]);
    }

    /**
     * DELETE /api/admin/user-subscriptions/{id}
     * حذف اشتراك
     */
    public function destroy(int $id): JsonResponse
    {
        $sub = UserPlanSubscription::findOrFail($id);
        $sub->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الاشتراك بنجاح',
        ]);
    }

    /**
     * POST /api/admin/user-subscriptions/{id}/add-ads
     * إضافة عدد إعلانات للاشتراك
     */
    public function addAds(Request $request, int $id): JsonResponse
    {
        $sub = UserPlanSubscription::findOrFail($id);

        $data = $request->validate([
            'count' => ['required', 'integer', 'min:1'],
        ]);

        $sub->increment('ads_total', $data['count']);

        return response()->json([
            'success' => true,
            'message' => "تم إضافة {$data['count']} إعلان للاشتراك ✅",
            'subscription' => $sub->refresh(),
        ]);
    }
}
