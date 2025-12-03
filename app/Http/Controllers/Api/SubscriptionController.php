<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoryPlanPrice;
use App\Models\UserPlanSubscription;
use App\Support\Section;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;


class SubscriptionController extends Controller
{
    public function pricesByCategory(Request $request)
    {
        $slug = $request->query('category_slug') ?? $request->query('slug');
        $sec = Section::fromSlug($slug);
        $row = CategoryPlanPrice::where('category_id', $sec->id())->first();
        return response()->json([
            'category_id' => $sec->id(),
            'category_slug' => $sec->slug,
            'price_featured' => (float) ($row->price_featured ?? 0),
            'featured_ad_price' => (float) ($row->featured_ad_price ?? 0),
            'featured_days' => (int) ($row->featured_days ?? 0),
            'price_standard' => (float) ($row->price_standard ?? 0),
            'standard_ad_price' => (float) ($row->standard_ad_price ?? 0),
            'standard_days' => (int) ($row->standard_days ?? 0),
        ]);
    }

    public function subscribe(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'category_slug' => ['required', 'string'],
            'plan_type' => [
                'required',
                'string',
                Rule::in(['featured', 'standard']), // ✅ مسموح بحاجتين بس
            ],
            'payment_reference' => ['required', 'string'],
        ]);

        $sec = Section::fromSlug($data['category_slug']);
        $prices = CategoryPlanPrice::where('category_id', $sec->id())->first();

        $plan = strtolower($data['plan_type']);
        $days = $plan === 'featured' ? (int) ($prices->featured_days ?? 0) : (int) ($prices->standard_days ?? 0);
        $price = $plan === 'featured' ? (float) ($prices->price_featured ?? 0) : (float) ($prices->price_standard ?? 0);
        $adPrice = $plan === 'featured' ? (float) ($prices->featured_ad_price ?? 0) : (float) ($prices->standard_ad_price ?? 0);

        $start = now();
        $expires = $days > 0 ? now()->copy()->addDays($days) : null;

        $sub = UserPlanSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $sec->id(),
                'plan_type' => $plan,
            ],
            [
                'days' => $days,
                'subscribed_at' => $start,
                'expires_at' => $expires,
                'price' => $price,
                'ad_price' => $adPrice,
                'payment_status' => 'paid',
                'payment_reference' => $data['payment_reference'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Subscription created',
            'subscription' => $sub,
        ], 201);
    }

    public function mySubscription(Request $request)
    {
        $user = $request->user();
        $slug = $request->query('category_slug');
        $sec = Section::fromSlug($slug);
        $sub = UserPlanSubscription::where('user_id', $user->id)
            ->where('category_id', $sec->id())
            ->orderByDesc('id')
            ->first();
        $active = false;
        if ($sub) {
            $active = !$sub->expires_at || $sub->expires_at->isFuture();
        }
        return response()->json([
            'active' => $active,
            'subscription' => $sub,
        ]);
    }
}
