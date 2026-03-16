<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DashboardListingUpdateRequest;
use App\Http\Resources\ListingResource;
use App\Models\CategoryBanner;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingService;
use App\Support\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardListingManagementController extends Controller
{
    public function show(Listing $listing, Request $request)
    {
        if ($response = $this->ensureDashboardListingAccess($request->user(), $listing)) {
            return $response;
        }

        $section = Section::fromId((int) $listing->category_id);
        abort_if(!$section, 404);

        $with = ['attributes', 'governorate', 'city', 'user'];
        if ($section->supportsMakeModel()) {
            $with[] = 'make';
            $with[] = 'model';
        }
        if ($section->supportsSections()) {
            $with[] = 'mainSection';
            $with[] = 'subSection';
        }

        $owner = User::query()
            ->select('id', 'name', 'phone', 'created_at')
            ->withCount('listings')
            ->find($listing->user_id);

        $userPayload = [
            'id' => $owner?->id,
            'name' => $owner?->name ?? 'advertiser',
            'phone' => $owner?->phone,
            'joined_at' => $owner?->created_at?->toIso8601String(),
            'joined_at_human' => $owner?->created_at?->diffForHumans(),
            'listings_count' => (int) ($owner?->listings_count ?? 0),
            'banner' => $this->resolveCategoryBannerPath($section->slug),
        ];

        return (new ListingResource($listing->load($with)))->additional([
            'user' => $userPayload,
        ]);
    }

    public function destroy(Listing $listing, Request $request): JsonResponse
    {
        if ($response = $this->ensureDashboardListingAccess($request->user(), $listing)) {
            return $response;
        }

        $listing->delete();

        return response()->json([
            'ok' => true,
            'message' => 'تم حذف الإعلان بنجاح',
        ]);
    }

    public function update(DashboardListingUpdateRequest $request, Listing $listing, ListingService $service)
    {
        if ($response = $this->ensureDashboardListingAccess($request->user(), $listing)) {
            return $response;
        }

        $section = Section::fromId((int) $listing->category_id);
        abort_if(!$section, 404);

        $data = $request->validated();

        $adminComment = $request->input('admin_comment');
        if ($adminComment !== null) {
            $data['admin_comment'] = $adminComment;
        }

        if ($request->hasFile('main_image')) {
            $data['main_image'] = $this->storeUploaded($request->file('main_image'), $section->slug, 'main');
        }

        if ($request->hasFile('images')) {
            $stored = [];
            foreach ($request->file('images') as $file) {
                $stored[] = $this->storeUploaded($file, $section->slug, 'gallery');
            }
            $data['images'] = $stored;
        }

        $updated = $service->update($section, $listing, $data);

        return new ListingResource($updated->load($this->listingRelationsForSection($section)));
    }

    protected function ensureDashboardListingAccess(?User $user, Listing $listing): ?JsonResponse
    {
        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح',
            ], 401);
        }

        if ($user->isAdmin()) {
            return null;
        }

        $requiredPageKey = $this->requiredDashboardPageForListing($listing);

        if ($user->hasDashboardPage($requiredPageKey)) {
            return null;
        }

        return response()->json([
            'message' => 'ليس لديك صلاحية تنفيذ هذا الإجراء على هذا الإعلان.',
            'required_page_key' => $requiredPageKey,
            'listing_status' => $listing->status,
        ], 403);
    }

    protected function requiredDashboardPageForListing(Listing $listing): string
    {
        if ($listing->status === 'Rejected') {
            return 'ads.moderation';
        }

        if ($listing->status === 'Pending') {
            return $this->isUnpaidPendingListing($listing)
                ? 'ads.unpaid'
                : 'ads.moderation';
        }

        return 'ads.list';
    }

    protected function isUnpaidPendingListing(Listing $listing): bool
    {
        return $listing->status === 'Pending'
            && $listing->publish_via === null
            && !(bool) $listing->isPayment
            && strtolower((string) ($listing->plan_type ?? 'free')) !== 'free';
    }

    protected function listingRelationsForSection(Section $section): array
    {
        $with = ['attributes', 'governorate', 'city', 'user'];

        if ($section->supportsMakeModel()) {
            $with[] = 'make';
            $with[] = 'model';
        }

        if ($section->supportsSections()) {
            $with[] = 'mainSection';
            $with[] = 'subSection';
        }

        return $with;
    }

    protected function storeUploaded($file, string $section, string $bucket = 'main'): string
    {
        $datePath = now()->format('Y/m');
        $dir = "uploads/{$section}/{$datePath}/" . ($bucket === 'main' ? 'main' : 'gallery');
        $name = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

        try {
            Storage::disk('public')->makeDirectory($dir);
            $path = $file->storeAs($dir, $name, 'public');
        } catch (\Throwable $e) {
            Log::error('dashboard_listing_update_upload_exception', [
                'section' => $section,
                'bucket' => $bucket,
                'dir' => $dir,
                'name' => $name,
                'error' => $e->getMessage(),
            ]);

            $field = $bucket === 'main' ? 'main_image' : 'images';
            throw \Illuminate\Validation\ValidationException::withMessages([$field => ['فشل رفع الملف.']]);
        }

        if (!$path) {
            Log::error('dashboard_listing_update_upload_failed', [
                'section' => $section,
                'bucket' => $bucket,
                'dir' => $dir,
                'name' => $name,
                'disk_root' => config('filesystems.disks.public.root'),
            ]);

            $field = $bucket === 'main' ? 'main_image' : 'images';
            throw \Illuminate\Validation\ValidationException::withMessages([$field => ['فشل رفع الملف.']]);
        }

        return $path;
    }

    protected function resolveCategoryBannerPath(string $slug): ?string
    {
        return Cache::remember("category_banner_path:{$slug}", now()->addMinutes(10), function () use ($slug) {
            $catBanner = CategoryBanner::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->value('banner_path');
            if (!empty($catBanner)) {
                return $catBanner;
            }

            $unifiedBanner = CategoryBanner::query()
                ->where('slug', 'unified')
                ->where('is_active', true)
                ->value('banner_path');
            if (!empty($unifiedBanner)) {
                return $unifiedBanner;
            }

            $unifiedPath = public_path('storage/uploads/banner/unified');
            if (File::isDirectory($unifiedPath)) {
                $files = File::files($unifiedPath);
                if (!empty($files)) {
                    return 'storage/uploads/banner/unified/' . $files[0]->getFilename();
                }
            }

            return null;
        });
    }
}
