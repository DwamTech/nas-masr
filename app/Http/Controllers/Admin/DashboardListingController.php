<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ListingController;
use App\Http\Requests\GenericListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\CategoryPlanPrice;
use App\Models\Listing;
use App\Models\SystemSetting;
use App\Services\AdminNotificationService;
use App\Services\ListingService;
use App\Support\Section;
use Illuminate\Http\JsonResponse;

class DashboardListingController extends ListingController
{
    public function store(
        string $section,
        GenericListingRequest $request,
        ListingService $service,
        AdminNotificationService $adminNotification
    ): JsonResponse
    {
        $user = $request->user();
        $sec = Section::fromSlug($section);
        $data = $request->validated();

        $data['rank'] = $this->getNextRank(Listing::class, $sec->id());

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->storeUploaded($request->file('main_image'), $section, 'main');
        }

        if ($request->hasFile('images')) {
            $stored = [];
            foreach ($request->file('images') as $file) {
                $stored[] = $this->storeUploaded($file, $section, 'gallery');
            }
            $data['images'] = $stored;
        }

        $data['status'] = 'Valid';
        $data['admin_approved'] = true;
        $data['published_at'] = now();
        $data['publish_via'] = 'admin';
        $data['isPayment'] = false;

        if (empty($data['expire_at'])) {
            $data['expire_at'] = $this->resolveDashboardExpiryDate($sec, (string) ($data['plan_type'] ?? 'free'));
        }

        $listing = $service->create($sec, $data, $user->id);

        return response()->json([
            'success' => true,
            'id' => $listing->id,
            'data' => (new ListingResource(
                $listing->load([
                    'attributes',
                    'governorate',
                    'city',
                    'make',
                    'model',
                    'mainSection',
                    'subSection',
                ])
            ))->resolve(),
            'payment' => [
                'type' => 'admin_bypass',
                'plan_type' => $data['plan_type'] ?? 'free',
                'price' => 0,
                'payment_reference' => null,
                'payment_method' => null,
                'currency' => $listing->currency,
                'user_id' => $user->id,
                'subscribed_at' => null,
            ],
        ], 201);
    }

    protected function resolveDashboardExpiryDate(Section $section, string $planType)
    {
        if ($planType !== 'free') {
            $planNorm = $this->normalizePlan($planType);
            $prices = CategoryPlanPrice::where('category_id', $section->id())->first();

            $days = $planNorm === 'featured'
                ? (int) ($prices?->featured_days ?? 30)
                : (int) ($prices?->standard_days ?? 30);

            return now()->addDays($days > 0 ? $days : 30);
        }

        $freeDays = SystemSetting::cachedPositiveInt('free_ad_days_validity', 365);

        return now()->addDays($freeDays > 0 ? $freeDays : 365);
    }
}
