<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenericListingRequest;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\ListingService;
use App\Support\Section;
use App\Traits\HasRank;
use App\Traits\PackageHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


class ListingController extends Controller
{
    use HasRank, PackageHelper;



    public function index(string $section, Request $request)
    {
        Listing::autoExpire();
        $sec = Section::fromSlug($section);
        $typesByKey = Listing::typesByKeyForSection($sec);

        $filterableKeys = collect($sec->fields)
            ->where('filterable', true)
            ->pluck('field_name')
            ->all();

        $with = ['attributes', 'governorate', 'city'];
        if ($sec->supportsMakeModel()) {
            $with[] = 'make';
            $with[] = 'model';
        }

        $q = Listing::query()
            ->forSection($sec)
            ->with($with)
            ->active()
            ->orderBy('rank', 'asc')
            ->keyword($request->query('q'))
            ->filterGovernorate($request->query('governorate_id'), $request->query('governorate'))
            ->filterCity($request->query('city_id'), $request->query('city'))
            ->priceRange($request->query('price_min'), $request->query('price_max'));

        if ($plan = $request->query('plan_type')) {

            $q->where('plan_type', $plan);
        }

        $attrEq = (array) $request->query('attr', []);
        $attrEq = array_intersect_key($attrEq, array_flip($filterableKeys));
        $q->attrEq($attrEq, $typesByKey);

        $attrIn = (array) $request->query('attr_in', []);
        $attrIn = array_intersect_key($attrIn, array_flip($filterableKeys));
        $q->attrIn($attrIn, $typesByKey);


        $attrMin = (array) $request->query('attr_min', []);
        $attrMax = (array) $request->query('attr_max', []);
        $attrMin = array_intersect_key($attrMin, array_flip($filterableKeys));
        $attrMax = array_intersect_key($attrMax, array_flip($filterableKeys));
        $q->attrRange($attrMin, $attrMax, $typesByKey);

        // attr_like = بحث نصي جزئي
        $attrLike = (array) $request->query('attr_like', []);
        $attrLike = array_intersect_key($attrLike, array_flip($filterableKeys));
        $q->attrLike($attrLike);


        $rows = $q->get();

        // زيّدي views لكل النتائج (بالشُحنات)
        if ($rows->isNotEmpty()) {
            $ids = $rows->pluck('id');
            $ids->chunk(1000)->each(function ($chunk) {
                DB::table('listings')
                    ->whereIn('id', $chunk)
                    ->update(['views' => DB::raw('views + 1')]);
            });
        }

        $supportsMakeModel = $sec->supportsMakeModel();
        $categorySlug = $sec->slug;
        $categoryName = $sec->name;


        $items = $rows->map(function ($item) use ($supportsMakeModel, $categorySlug, $categoryName) {
            // attributes (EAV) كاملة
            $attrs = [];
            if ($item->relationLoaded('attributes')) {
                foreach ($item->attributes as $row) {
                    $attrs[$row->key] = $this->castEavValueRow($row);
                }
            }

            $data = [
                'attributes'      => $attrs,
                'governorate'     => ($item->relationLoaded('governorate') && $item->governorate) ? $item->governorate->name : null,
                'city'            => ($item->relationLoaded('city') && $item->city) ? $item->city->name : null,
                'price'           => $item->price,
                'contact_phone'   => $item->contact_phone,
                'whatsapp_phone'  => $item->whatsapp_phone,
                'main_image_url'  => $item->main_image ? asset('storage/' . $item->main_image) : null,
                'created_at'      => $item->created_at,
                'plan_type'       => $item->plan_type,
                'views'           => $item->views,
                'rank'            => $item->rank,
                'id' => $item->id,
                'lat' => $item->lat,
                'lng' => $item->lng,

                // الكاتيجري
                'category'        => $categorySlug,   // slug
                'category_name'   => $categoryName,   // الاسم
            ];

            if ($supportsMakeModel) {
                $data['make']  = ($item->relationLoaded('make')  && $item->make)  ? $item->make->name  : null;
                $data['model'] = ($item->relationLoaded('model') && $item->model) ? $item->model->name : null;
            }

            return $data;
        })->values();

        return response()->json($items);
    }

    /** نفس منطق قراءة قيمة الـ EAV */
    protected function castEavValueRow($attr)
    {
        return $attr->value_int
            ?? $attr->value_decimal
            ?? $attr->value_bool
            ?? $attr->value_string
            ?? $this->decodeJsonSafe($attr->value_json)
            ?? $attr->value_date
            ?? null;
    }

    protected function decodeJsonSafe($json)
    {
        if (is_null($json)) return null;
        if (is_array($json)) return $json;

        $x = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $x : $json;
    }



    public function store(string $section, GenericListingRequest $request, ListingService $service)
    {
        $user = $request->user();
        $sec  = Section::fromSlug($section);
        $data = $request->validated();

        $rank = $this->getNextRank(Listing::class, $sec->id());
        $data['rank'] = $rank;

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

        $manualApprove = Cache::remember('settings:manual_approval', now()->addHours(6), function () {
            $val = SystemSetting::where('key', 'manual_approval')->value('value');
            return (int) $val === 1;
        });

        $paymentRequired = false;
        $packageData     = null;

        if ($data['plan_type'] !== 'free') {
            $packageResult = $this->consumeForPlan($user->id, $data['plan_type']);
            $packageData   = $packageResult->getData(true);

            if (empty($packageData['success']) || $packageData['success'] === false) {
                $paymentRequired = true;
            } else {
                $data['expire_at'] = Carbon::parse($packageData['expire_date']);
            }
        }

        if ($paymentRequired) {
            $data['status']         = 'Pending';
            $data['admin_approved'] = false;
        } else {
            if ($manualApprove) {
                $data['status']         = 'Pending';
                $data['admin_approved'] = false;
            } else {
                $data['status']         = 'Valid';
                $data['admin_approved'] = true;
                $data['published_at']   = now();
                if ($data['plan_type'] == 'free') {
                    $data['expire_at']      = now()->addDays(365);
                }
            }
        }

        $listing = $service->create($sec, $data, $user->id);

        $user->role = 'advertiser';
        $user->save();

        if ($paymentRequired) {
            return response()->json([
                'success'          => false,
                'message'          => $packageData['message'] ?? 'لا تملك باقة فعّالة، يجب عليك دفع قيمة هذا الإعلان.',
                'payment_required' => true,
                'listing_id'       => $listing->id,
            ], 402);
        }

        return new ListingResource(
            $listing->load(['attributes', 'governorate', 'city', 'make', 'model'])
        );
    }

    public function show(string $section, Listing $listing)
    {
        $sec = Section::fromSlug($section);
        abort_if($listing->category_id !== $sec->id(), 404);


        $listing->increment('views');
        $banner = null;
        if ($sec->slug == "real_estate") {
            $banner = "storage/uploads/banner/796c4c36d93281ccfb0cac71ed31e5d1b182ae79.png";
        };

        $owner =    User::select('id', 'name', 'created_at')->find($listing->user_id);
        $adsCount = Listing::where('user_id', $listing->user_id)->count();

        $userPayload = [
            'id'               => $owner?->id,
            'name'             => $owner?->name ?? "advertiser",
            'joined_at'        => $owner?->created_at?->toIso8601String(),
            'joined_at_human'  => $owner?->created_at?->diffForHumans(),
            'listings_count'   => $adsCount,
            'banner' => $banner
        ];


        return (new ListingResource(
            $listing->load(['attributes', 'governorate', 'city', 'make', 'model'])
        ))->additional([
            'user' => $userPayload,
        ]);
    }

    protected function userIsAdmin($user): bool
    {
        return $user->role == 'admin';
    }

    public function update(string $section, GenericListingRequest $request, Listing $listing, ListingService $service)
    {
        $sec = Section::fromSlug($section);
        abort_if($listing->category_id !== $sec->id(), 404);

        $user = $request->user();
        $isOwner = $listing->user_id === ($user->id);
        $isAdmin = $this->userIsAdmin($user);

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'message' => 'غير مصرح لك بتعديل هذا الإعلان.'
            ], 403);
        }

        $data = $request->validated();

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

        $listing = $service->update($sec, $listing, $data);

        return new ListingResource($listing->load(['attributes', 'governorate', 'city', 'make', 'model']));
    }

    public function destroy(string $section, Listing $listing)
    {
        $sec = Section::fromSlug($section);
        abort_if($listing->category_id !== $sec->id(), 404);

        $user = request()->user();
        $isOwner = $listing->user_id === ($user->id ?? null);
        $isAdmin = $this->userIsAdmin($user);

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'message' => 'غير مصرح لك بحذف هذا الإعلان.'
            ], 403);
        }

        $listing->delete();

        return response()->json(['ok' => true]);
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
            Log::error('upload_store_exception', [
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
            Log::error('upload_store_failed', [
                'section' => $section,
                'bucket' => $bucket,
                'dir' => $dir,
                'name' => $name,
                'disk_root' => config('filesystems.disks.public.root'),
            ]);
            $field = $bucket === 'main' ? 'main_image' : 'images';
            throw \Illuminate\Validation\ValidationException::withMessages([$field => ['فشل رفع الملف.']]);
        }
        if (!Storage::disk('public')->exists($path)) {
            Log::error('upload_file_missing_after_store', [
                'path' => $path,
                'dir' => $dir,
            ]);
            $field = $bucket === 'main' ? 'main_image' : 'images';
            throw \Illuminate\Validation\ValidationException::withMessages([$field => ['فشل حفظ الملف.']]);
        }
        return $path;
    }
}
