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
use App\Models\UserPlanSubscription;
use App\Models\CategoryPlanPrice;
use App\Services\NotificationService;


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
        if ($sec->supportsSections()) {
            $with[] = 'mainSection';
            $with[] = 'subSection';
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
        if ($sec->supportsMakeModel()) {
            $makeId = $request->query('make_id');
            $makeName = $request->query('make');
            $modelId = $request->query('model_id');
            $modelName = $request->query('model');

            if ($makeId) {
                $q->where('make_id', (int) $makeId);
            } elseif ($makeName) {
                $q->whereHas('make', function ($qq) use ($makeName) {
                    $qq->where('name', 'like', '%' . trim($makeName) . '%');
                });
            }

            if ($modelId) {
                $q->where('model_id', (int) $modelId);
            } elseif ($modelName) {
                $q->whereHas('model', function ($qq) use ($modelName) {
                    $qq->where('name', 'like', '%' . trim($modelName) . '%');
                });
            }
        }
        if ($sec->supportsSections()) {
            $mainSectionId = $request->query('main_section_id');
            $subSectionId = $request->query('sub_section_id');
            $mainSectionName = $request->query('main_section');
            $subSectionName = $request->query('sub_section');

            if ($mainSectionId) {
                $q->where('main_section_id', $mainSectionId);
            } elseif ($mainSectionName) {
                $q->whereHas('mainSection', function ($qq) use ($mainSectionName) {
                    $qq->where('name', $mainSectionName);
                });
            }

            if ($subSectionId) {
                $q->where('sub_section_id', $subSectionId);
            } elseif ($subSectionName) {
                $q->whereHas('subSection', function ($qq) use ($subSectionName) {
                    $qq->where('name', $subSectionName);
                });
            }
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
        // if ($rows->isNotEmpty()) {
        //     $ids = $rows->pluck('id');
        //     $ids->chunk(1000)->each(function ($chunk) {
        //         DB::table('listings')
        //             ->whereIn('id', $chunk)
        //             ->update(['views' => DB::raw('views + 1')]);
        //     });
        // }

        $supportsMakeModel = $sec->supportsMakeModel();
        $supportsSections = $sec->supportsSections();

        $categorySlug = $sec->slug;
        $categoryName = $sec->name;


        $items = $rows->map(function ($item) use ($supportsMakeModel, $supportsSections, $categorySlug, $categoryName) {
            // attributes (EAV) كاملة
            $attrs = [];
            if ($item->relationLoaded('attributes')) {
                foreach ($item->attributes as $row) {
                    $attrs[$row->key] = $this->castEavValueRow($row);
                }
            }

            $data = [
                'attributes' => $attrs,
                'governorate' => ($item->relationLoaded('governorate') && $item->governorate) ? $item->governorate->name : null,
                'city' => ($item->relationLoaded('city') && $item->city) ? $item->city->name : null,
                'price' => $item->price,
                'contact_phone' => $item->contact_phone,
                'whatsapp_phone' => $item->whatsapp_phone,
                'main_image_url' => $item->main_image ? asset('storage/' . $item->main_image) : null,
                'created_at' => $item->created_at,
                'plan_type' => $item->plan_type,
                'views' => $item->views,
                'rank' => $item->rank,
                'id' => $item->id,
                'lat' => $item->lat,
                'lng' => $item->lng,

                // الكاتيجري
                'category' => $categorySlug,   // slug
                'category_name' => $categoryName,   // الاسم
            ];

            if ($supportsMakeModel) {
                $data['make'] = ($item->relationLoaded('make') && $item->make) ? $item->make->name : null;
                $data['model'] = ($item->relationLoaded('model') && $item->model) ? $item->model->name : null;
            }
            if ($supportsSections) {
                $data['main_section_id'] = $item->main_section_id;
                $data['main_section'] = ($item->relationLoaded('mainSection') && $item->mainSection)
                    ? $item->mainSection->name
                    : null;

                $data['sub_section_id'] = $item->sub_section_id;
                $data['sub_section'] = ($item->relationLoaded('subSection') && $item->subSection)
                    ? $item->subSection->name
                    : null;
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
        if (is_null($json))
            return null;
        if (is_array($json))
            return $json;

        $x = json_decode($json, true);
        return json_last_error() === JSON_ERROR_NONE ? $x : $json;
    }



    public function store(string $section, GenericListingRequest $request, ListingService $service)
    {
        $user = $request->user();
        $sec = Section::fromSlug($section);
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
        $packageData = null;
        $activeSub = null;
        $paymentType = null;
        $paymentReference = null;
        $paymentMethod = null;
        $priceOut = 0.0;

        if (!empty($data['plan_type']) && $data['plan_type'] !== 'free') {
            $planNorm = $this->normalizePlan($data['plan_type']);
            $activeSub = UserPlanSubscription::query()
                ->where('user_id', $user->id)
                ->where('category_id', $sec->id())
                ->where('plan_type', $planNorm)
                ->where('payment_status', 'paid')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
                })
                ->first();

            if ($activeSub) {
                // [MODIFIED] Check & Consume Ad
                if (!$activeSub->consumeAd(1)) {
                    $paymentRequired = true; // Fallback to payment if ads are exhausted
                    // If you want to strictly block, return here. 
                    // But maybe user wants to pay for this specific ad?
                    // Let's assume we want to block "Subscription usage" but allow "Paying per ad".
                    // However, original logic falls through to check other methods? No, it's an if-else block.
                    
                    // Logic: If subscription exists but no ads, user must renew or pay per ad.
                    // We will set $activeSub to null to force falling into "else" block (Package or Pay per ad)?
                    // Or we explicitly return error?
                    // Based on req: "Block if 0".
                    
                    $activeSub = null; // Treat as if no active subscription for this flow
                     // We continue to the 'else' block which checks for Packages or forces Payment.
                     // But wait, the 'else' block checks for Packages.
                     // If we want to allow paying for this ad individually, we just let it fall through.
                     // If we want to tell them "Your subscription is empty", we might need a specific message.
                     
                     // Let's rely on standard flow: if sub is empty, it's not "active" for this purpose.
                } else {
                    $data['expire_at'] = $activeSub->expires_at;
                    $paymentType = 'subscription';
                    $paymentReference = $activeSub->payment_reference;
                    $paymentMethod = $activeSub->payment_method;
                    $priceOut = (float) ($activeSub->price ?? 0);
                    $data['publish_via'] = env('LISTING_PUBLISH_VIA_SUBSCRIPTION', 'subscription');
                }
            } else {
                $packageResult = $this->consumeForPlan($user->id, $planNorm);
                $packageData   = $packageResult->getData(true);

                if (empty($packageData['success']) || $packageData['success'] === false) {
                    $paymentRequired = true;
                    $message = ' لا تملك باقة فعّالة، يجب عليك دفع قيمة هذا الإعلان.او الاشتراك في باقه';
                } else {
                    $data['expire_at'] = Carbon::parse($packageData['expire_date']);
                    $paymentType = 'package';
                    $prices = CategoryPlanPrice::where('category_id', $sec->id())->first();
                    $priceOut = $planNorm === 'featured'
                        ? (float) ($prices?->featured_ad_price ?? 0)
                        : (float) ($prices?->standard_ad_price ?? 0);
                    $data['publish_via'] = env('LISTING_PUBLISH_VIA_PACKAGE', 'package');
                }
            }
        } else {
            $freeVia = env('LISTING_PUBLISH_VIA_FREE', 'free');
            if ($sec->slug != 'missing') {
                $freeCount = Cache::remember('settings:free_ads_count', now()->addHours(6), function () {
                    return (int) (SystemSetting::where('key', 'free_ads_count')->value('value') ?? 0);
                });
                $freeMaxPrice = Cache::remember('settings:free_ads_max_price', now()->addHours(6), function () {
                    return (int) (SystemSetting::where('key', 'free_ads_max_price')->value('value') ?? 0);
                });

                $userFreeCount = Listing::query()
                    ->where('user_id', $user->id)
                    ->where(function ($q) use ($freeVia) {
                        $q->where('publish_via', $freeVia)->orWhere('plan_type', 'free');
                    })
                    ->whereIn('status', ['Valid', 'Pending'])
                    ->count();

                $priceVal = (float) ($data['price'] ?? 0);
                $overCount = ($freeCount > 0 && $userFreeCount >= $freeCount);
                $overPrice = ($freeMaxPrice > 0 && $priceVal > $freeMaxPrice);

                if ($overCount || $overPrice) {
                    // $paymentRequired = true;

                    $message = null;

                    if ($overCount && $overPrice) {
                        return Response()->json([
                            'success' => false,
                            'message' => ' لقد تجاوزت الحد الأقصى لعدد الإعلانات المجانية في هذا القسم، كما أن سعر هذا الإعلان أعلى من الحد المسموح به للإعلان المجاني. لنشر هذا الإعلان، يُرجى الاشتراك في باقة مدفوعة أو دفع تكلفة إعلان منفرد مع تغير نوع الخطه  لهذا الاعلان .',
                        ], 402);
                    } elseif ($overCount) {
                        return Response()->json([
                            'success' => false,
                            'message' => ' لقد تجاوزت الحد الأقصى لعدد الإعلانات المجانية المسموح بها في هذا القسم. لنشر المزيد من الإعلانات، يُرجى الاشتراك في باقة مدفوعة أو دفع تكلفة إعلان منفرد. مع تغير نوع الخطه  لهذا الاعلان'

                        ], 402);
                    } elseif ($overPrice) {
                        return Response()->json([
                            'success' => false,
                            'message' => 'سعر هذا الإعلان أعلى من الحد الأقصى المسموح به للإعلان المجاني في هذا القسم. يمكنك إمّا تخفيض السعر ليتوافق مع الحد المجاني أو الاشتراك في باقة مدفوعة لنشر الإعلان. مع تغير نوع الخطه  لهذا الاعلان'
                        ], 402);
                    }
                }
            } else {
                $data['publish_via'] = $freeVia;
                $paymentType = 'free';
                $priceOut = 0.0;
            }
        }



        if ($paymentRequired) {
            $data['status'] = 'Pending';
            $data['admin_approved'] = false;
        } else {
            if ($manualApprove) {
                $data['status'] = 'Pending';
                $data['admin_approved'] = false;
            } else {
                $data['status'] = 'Valid';
                $data['admin_approved'] = true;
                $data['published_at'] = now();
                if (($data['plan_type'] ?? 'free') === 'free' && empty($data['expire_at'])) {
                    $data['expire_at'] = now()->addDays(365);
                }
                if (($data['plan_type'] ?? 'free') === 'free') {
                    $paymentType = 'free';
                    $priceOut = 0.0;
                    $data['publish_via'] = env('LISTING_PUBLISH_VIA_FREE', 'free');
                }
            }
        }

        $listing = $service->create($sec, $data, $user->id);

        $user->role = 'advertiser';
        $user->save();

        if ($paymentRequired) {
            return response()->json([
                'success' => false,
                'message' =>  $message,
                'payment_required' => true,
                'listing_id' => $listing->id,
                // 'count'=>$userFreeCount,
            ], 402);
        }

        return (new ListingResource(
            $listing->load([
                'attributes',
                'governorate',
                'city',
                'make',
                'model',
                'mainSection',
                'subSection',
            ])
        ))->additional([
            'payment' => [
                'type' => $paymentType,
                'plan_type' => $data['plan_type'] ?? 'free',
                'price' => $priceOut,
                'payment_reference' => $paymentReference,
                'payment_method' => $paymentMethod,
                'currency' => $listing->currency,
                'user_id' => $user->id,
                'subscribed_at' => $activeSub?->subscribed_at,
            ],
        ]);
    }

    public function show(string $section, Listing $listing, NotificationService $notifications)
    {
        $sec = Section::fromSlug($section);
        abort_if($listing->category_id !== $sec->id(), 404);


        $listing->increment('views');
        $viewer = request()->user();
        if ($viewer->role != 'admin') {
            if ($viewer && $viewer->id !== $listing->user_id) {
                $notifications->dispatch(
                    (int) $listing->user_id,
                    'تمت مشاهدة إعلانك',
                    'قام المستخدم #' . $viewer->id . ' بمشاهدة إعلانك #' . $listing->id,
                    'view',
                    ['viewer_id' => (int) $viewer->id, 'listing_id' => (int) $listing->id]
                );
            }
        }

        $banner = null;
        if ($sec->slug == "real_estate") {
            $banner = "storage/uploads/banner/796c4c36d93281ccfb0cac71ed31e5d1b182ae79.png";
        };

        $owner = User::select('id', 'name', 'created_at')->find($listing->user_id);
        $adsCount = Listing::where('user_id', $listing->user_id)->count();

        $userPayload = [
            'id' => $owner?->id,
            'name' => $owner?->name ?? "advertiser",
            'joined_at' => $owner?->created_at?->toIso8601String(),
            'joined_at_human' => $owner?->created_at?->diffForHumans(),
            'listings_count' => $adsCount,
            'banner' => $banner
        ];


        return (new ListingResource(
            $listing->load([
                'attributes',
                'governorate',
                'city',
                'make',
                'model',
                'mainSection',
                'subSection',
            ])
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
        if ($isAdmin) {
            $adminComment = $request->input('admin_comment');
            if ($adminComment !== null) {
                $data['admin_comment'] = $adminComment;
            }
        }

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

        return new ListingResource($listing->load([
            'attributes',
            'governorate',
            'city',
            'make',
            'model',
            'mainSection',
            'subSection',
        ]));
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
