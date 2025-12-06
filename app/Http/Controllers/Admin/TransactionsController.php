<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingPayment;
use App\Models\UserPlanSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 100);
        $userId = $request->query('user_id');
        $categoryId = $request->query('category_id');
        $planType = $request->query('plan_type');
        $from = $request->query('from');
        $to = $request->query('to');

        $ads = ListingPayment::query()
            ->with(['listing:id,title,category_id,plan_type', 'user:id,name'])
            ->when($userId, fn($q) => $q->where('user_id', (int) $userId))
            ->when($categoryId, fn($q) => $q->where('category_id', (int) $categoryId))
            ->when($planType, fn($q) => $q->where('plan_type', $planType))
            ->when($from, fn($q) => $q->where('paid_at', '>=', $from))
            ->when($to, fn($q) => $q->where('paid_at', '<=', $to))
            ->orderByDesc('paid_at')
            ->paginate($perPage);

        $adsItems = collect($ads->items())->map(function (ListingPayment $p) {
            return [
                'type' => 'ad_payment',
                'id' => $p->id,
                'user_id' => $p->user_id,
                'user_name' => optional($p->user)->name,
                'listing_id' => $p->listing_id,
                'listing_title' => optional($p->listing)->title,
                'category_id' => $p->category_id,
                'plan_type' => $p->plan_type,
                'amount' => (float) $p->amount,
                'currency' => $p->currency,
                'paid_at' => optional($p->paid_at)->toIso8601String(),
                'payment_method' => $p->payment_method,
                'payment_reference' => $p->payment_reference,
                'status' => $p->status,
            ];
        })->values();

        $subs = UserPlanSubscription::query()
            ->with(['user:id,name'])
            ->when($userId, fn($q) => $q->where('user_id', (int) $userId))
            ->when($categoryId, fn($q) => $q->where('category_id', (int) $categoryId))
            ->when($planType, fn($q) => $q->where('plan_type', $planType))
            ->when($from, fn($q) => $q->where('subscribed_at', '>=', $from))
            ->when($to, fn($q) => $q->where('subscribed_at', '<=', $to))
            ->orderByDesc('subscribed_at')
            ->paginate($perPage);

        $subsItems = collect($subs->items())->map(function (UserPlanSubscription $s) {
            return [
                'type' => 'subscription',
                'id' => $s->id,
                'user_id' => $s->user_id,
                'user_name' => optional($s->user)->name,
                'category_id' => $s->category_id,
                'plan_type' => $s->plan_type,
                'price' => (float) $s->price,
                'ad_price' => (float) $s->ad_price,
                'payment_method' => $s->payment_method,
                'payment_reference' => $s->payment_reference,
                'subscribed_at' => optional($s->subscribed_at)->toIso8601String(),
                'expires_at' => optional($s->expires_at)->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'ads' => [
                'meta' => [
                    'page' => $ads->currentPage(),
                    'per_page' => $ads->perPage(),
                    'total' => $ads->total(),
                    'last_page' => $ads->lastPage(),
                ],
                'items' => $adsItems,
            ],
            'subscriptions' => [
                'meta' => [
                    'page' => $subs->currentPage(),
                    'per_page' => $subs->perPage(),
                    'total' => $subs->total(),
                    'last_page' => $subs->lastPage(),
                ],
                'items' => $subsItems,
            ],
        ]);
    }
}

