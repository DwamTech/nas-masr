<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ListingResource;
use App\Models\Listing;
use App\Models\User;
use App\Models\UserClient;
use App\Models\UserPackages;
use App\Support\Section;
use App\Traits\HasRank;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;


class UserController extends Controller
{
    //
    use HasRank;

    public function getUserProfile()
    {
        $user = Auth::user();
        $code = UserClient::where('user_id', $user->id)->first();

        if (!$user) {
            return response([
                'message' => 'User not authenticated'
            ], 401);
        }

        return response([
            'message' => 'Profile fetched successfully',
            'data' => $user,
            'code' => $code->user_id ?? null
        ], 200);
    }

    public function editProfile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response([
                'message' => 'User not authenticated'
            ], 401);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'password' => ['sometimes', 'string'],
            'lat' => ['sometimes', 'nullable', 'numeric'],
            'lng' => ['sometimes', 'nullable', 'numeric'],
            'referral_code' => ['sometimes', 'nullable', 'string'],
            'address' => ['sometimes', 'nullable', 'string'],
        ]);

        if (!empty($validated['referral_code'])) {
            $code = UserClient::where('user_id', $validated['referral_code'])->first();
            if (!$code) {
                return response([
                    'message' => 'Referral code not found'
                ], 404);
            }
            $clients = $code->clients ?? [];


            if (in_array($user->id, $clients)) {
                return response()->json([
                    "message" => "You have already used this referral code."
                ]);
            }

            $clients[] = $user->id;


            $code->clients = $clients;
            $code->save();
        }
        $user->update($validated);

        return response([
            'message' => 'Profile updated successfully',
            'data' => $user->fresh(),

        ], 200);
    }

    //my ads 
    public function myAds(Request $request)
    {
        $user = $request->user();
        $slug = $request->query('category_slug');
        $status = $request->query('status');
        $categoryId = null;
        // $supportsMakeModel = false;

        if ($slug) {
            $section = Section::fromSlug($slug);
            $categoryId = $section->id();
            // $supportsMakeModel = $section->supportsMakeModel();
        }

        // Build query
        $q = Listing::query()
            ->where('user_id', $user->id)
            // ->where('status', 'Valid')
            ->orderBy('rank', 'desc')
            ->orderBy('published_at', 'desc')
            ->orderBy('id', 'desc')
            ->with(['attributes', 'governorate', 'city', 'make', 'model']);

        if ($categoryId) {
            $q->where('category_id', $categoryId);
        }
        if ($status) {
            $q->where('status', $status);
        }

        // if ($supportsMakeModel) {
        //     $q->with(['make', 'model']);
        // }

        $items = $q->get();

        return ListingResource::collection($items)
            ->additional([
                'category_slug' => $slug,
                // 'supports_make_model' => $supportsMakeModel,
            ]);
    }


    public function myPackages(Request $request)
    {
        $user = $request->user();
        $pkg  = UserPackages::where('user_id', $user->id)->first();

        $makeCardLite = function (string $titleAr, bool $active, ?\Illuminate\Support\Carbon $expiresAt): array {
            return [
                'title'              => $titleAr,
                'badge_text'         => $active ? 'نشط' : 'منتهي',
                'expires_at_human'   => $expiresAt?->translatedFormat('d/m/Y'),
                'note'               => $expiresAt
                    ? 'تنتهي صلاحية الإعلانات والباقة بتاريخ ' . $expiresAt->translatedFormat('d/m/Y')
                    : null,
            ];
        };

        if (!$pkg) {
            return response()->json([
                'packages' => [
                    $makeCardLite('الباقة المتميزة', false, null),
                    $makeCardLite('الباقة الاستاندرد', false, null),
                ],
            ]);
        }

        $exp = $pkg->expire_date instanceof Carbon
            ? $pkg->expire_date
            : ($pkg->expire_date ? Carbon::parse($pkg->expire_date) : null);

        $isActive = ($exp === null) || $exp->isFuture();

        return response()->json([
            'packages' => [
                $makeCardLite('الباقة المتميزة', $isActive && $pkg->featured_ads_remaining > 0, $exp),
                $makeCardLite('الباقة الاستاندرد', $isActive && $pkg->standard_ads_remaining > 0, $exp),
            ],
        ]);
    }

    //   public function makeRankOne(Request $request)
    // {
    //     $validated = $request->validate([
    //         'category' => ['required', 'string'], // slug
    //         'ad_id'    => ['required', 'integer'],
    //     ]);

    //     // حدد القسم من الـ slug
    //     $sec = Section::fromSlug($validated['category']);
    //     $categoryId = $sec->id();

    //     // هات الإعلان وتأكد إنه تبع نفس القسم
    //     $ad = Listing::where('id', $validated['ad_id'])
    //         ->where('category_id', $categoryId)
    //         ->first();

    //     if (!$ad) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'الإعلان غير موجود في هذا القسم.',
    //         ], 404);
    //     }

    //     // السماح فقط لمالك الإعلان (أو أدمن لو حابب تضيف سماحية)
    //     $user = $request->user();
    //     if ($ad->user_id !== ($user->id ?? null)) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'لا يمكنك تعديل ترتيب إعلان لا تملكه.',
    //         ], 403);
    //     }

    //     // كاش/كوول-داون: مرة كل 24 ساعة لنفس (user, category, ad)
    //     $cooldownHours = 24;
    //     $cacheKey = "rank1:{$user->id}:cat{$categoryId}:ad{$ad->id}";
    //     if (Cache::has($cacheKey)) {
    //         $expiresAtTs = Cache::get($cacheKey);
    //         $remaining   = max(0, $expiresAtTs - now()->timestamp);
    //         $hrs         = (int) ceil($remaining / 3600);

    //         return response()->json([
    //             'status'  => false,
    //             'message' => "يمكنك رفع الإعلان مرة كل 24 ساعة. المُتبقي تقريبًا: {$hrs} ساعة.",
    //         ], 429);
    //     }


    //     $ok = $this->makeRankOne($categoryId, $ad->id);
    //     if (!$ok) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'حدث خطأ أثناء تحديث الترتيب.',
    //         ], 500);
    //     }

    //     // ثبت الكوول-داون
    //     $expires = now()->addHours($cooldownHours);
    //     Cache::put($cacheKey, $expires->timestamp, $expires);

    //     return response()->json([
    //         'status'  => true,
    //         'message' => "تم رفع الإعلان #{$ad->id} إلى الترتيب الأول في قسم {$sec->name}.",
    //     ]);
    // }

    public function logout(Request $request)
    {
        $user = $request->user();

        $user->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Successfully logged out from API'
        ], 200);
    }

    //Admin control
    public function blockedUser(Request $request, User $user)
    {
        if ($user->status === 'blocked') {
            $user->update([
                'status' => 'active'
            ]);
            return response()->json([
                'message' => 'User unblocked successfully.'
            ], 200);
        } else {
            $user->update([
                'status' => 'blocked'
            ]);
            $user->tokens()->delete();

            return response()->json([
                'message' => 'User blocked successfully.'
            ], 200);
        }
    }

    // Admin: Show user details
    // public function showUser(User $user)
    // {
    //     $user->loadCount('listings');

    //     return response()->json([
    //         'id' => $user->id,
    //         'name' => $user->name,
    //         'phone' => $user->phone,
    //         'user_code' => $user->referral_code ?: (string) $user->id,
    //         'status' => $user->status ?? 'active',
    //         'registered_at' => optional($user->created_at)->toDateString(),
    //         'listings_count' => $user->listings_count ?? 0,
    //         'role' => $user->role ?? 'user',
    //     ]);
    // }

    // Admin: Show user with listings combined
    public function showUserWithListings(User $user, Request $request)
    {
        $user->loadCount('listings');

        // params
        $singleSlug  = $request->query('category_slug') ?? $request->query('slug');
        $multiSlugs  = $request->query('category_slugs') ?? $request->query('slugs'); // "a,b,c"
        $statusFilter = $request->query('status'); // Valid / Pending / Rejected / Expired

        // جهّز مصفوفة slugs
        $slugs = [];
        if ($singleSlug) {
            $slugs[] = trim($singleSlug);
        }
        if ($multiSlugs) {
            $extra  = array_map('trim', explode(',', $multiSlugs));
            $slugs  = array_values(array_filter(array_merge($slugs, $extra)));
        }

        // نفس منطق الاستعلام، مع eager-load للعلاقات المطلوبة
        $query = Listing::query()
            ->leftJoin('categories', 'listings.category_id', '=', 'categories.id')
            ->with(['attributes', 'governorate', 'city', 'make', 'model'])
            ->where('listings.user_id', $user->id)
            ->when($statusFilter, fn($q) => $q->where('listings.status', $statusFilter))
            ->when(!empty($slugs), fn($q) => $q->whereIn('categories.slug', $slugs))
            ->select([
                'listings.id',
                'listings.category_id',
                'listings.main_image',
                'listings.price',
                'listings.rank',
                'listings.views',
                'listings.lat',
                'listings.lng',
                'listings.contact_phone',
                'listings.whatsapp_phone',
                'listings.plan_type',
                'listings.created_at',
                'categories.slug as category_slug',

            ])
            ->orderByDesc('listings.created_at');

        // بدون Pagination: رجّع الكل
        $rows = $query->get();

        // حوِّل لكل عنصر الـ minimal payload المطلوب + category بالعربي/الإنجليزي
        $items = $rows->map(function ($row) {
            // attributes كاملة (EAV)
            $attrs = [];
            if ($row->relationLoaded('attributes')) {
                foreach ($row->attributes as $attr) {
                    $attrs[$attr->key] = $this->castEavValueRow($attr);
                }
            }

            // أسماء المحافظة/المدينة
            $govName  = ($row->relationLoaded('governorate') && $row->governorate) ? $row->governorate->name : null;
            $cityName = ($row->relationLoaded('city') && $row->city) ? $row->city->name : null;

            // بيانات القسم (slug + name)
            $catSlug = $row->category_slug;
            $catName = null;
            if ($row->category_id) {
                $sec = Section::fromId($row->category_id);
                $catSlug = $sec?->slug ?? $catSlug;
                $catName = $sec?->name ?? null;
            }

            // make/model حسب القسم
            $supportsMakeModel = false;
            if ($row->category_id) {
                $sec = Section::fromId($row->category_id);
                $supportsMakeModel = $sec?->supportsMakeModel() ?? false;
            }

            $data = [
                'attributes'      => $attrs,
                'governorate'     => $govName,
                'city'            => $cityName,
                'price'           => $row->price,
                'contact_phone'   => $row->contact_phone,
                'whatsapp_phone'  => $row->whatsapp_phone,
                'main_image_url'  => $row->main_image ? asset('storage/' . $row->main_image) : null,
                'created_at'      => $row->created_at,
                'plan_type'       => $row->plan_type,
                'id' => $row->id,
                'lat' => $row->lat,
                'lng' => $row->lng,
                'rank' => $row->rank,
                'views' => $row->views,



                // ✅ الكاتيجري بالإنجليزي (slug) وبالعربي (name)
                'category'        => $catSlug,
                'category_name'   => $catName,
            ];

            if ($supportsMakeModel) {
                $data['make']  = ($row->relationLoaded('make')  && $row->make)  ? $row->make->name  : null;
                $data['model'] = ($row->relationLoaded('model') && $row->model) ? $row->model->name : null;
            }

            return $data;
        })->values();

        return response()->json([
            // 'user'     => $this->formatUserSummary($user),
            'listings' => $items,
            'meta'     => ['total' => $items->count()], // بدون pagination
        ]);
    }


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


    // Admin: Create user
    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:15', 'unique:users,phone'],
            'role' => ['nullable', Rule::in(['user', 'advertiser', 'admin', 'reviewer'])],
            'status' => ['nullable', Rule::in(['active', 'blocked'])],
            'referral_code' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:4', 'max:100'],
        ]);

        $user = new User();
        $user->name = $data['name'] ?? ($data['phone'] ?? 'User');
        $user->phone = $data['phone'];
        $user->role = $data['role'] ?? 'user';
        $user->status = $data['status'] ?? 'active';
        $user->referral_code = $data['referral_code'] ?? null;
        $user->password = Hash::make($data['password'] ?? '123456');
        $user->save();

        $user->loadCount('listings');

        return response()->json([
            'message' => 'User created successfully',
            'user' => $this->formatUserSummary($user),
        ], 201);
    }

    // Admin: Update user data
    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:15', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['nullable', Rule::in(['user', 'advertiser', 'admin', 'reviewer'])],
            'status' => ['nullable', Rule::in(['active', 'blocked'])],
            'referral_code' => ['nullable', 'string', 'max:20'],
        ]);

        foreach (['name', 'phone', 'role', 'status', 'referral_code'] as $field) {
            if (array_key_exists($field, $data)) {
                $user->{$field} = $data[$field];
            }
        }
        $user->save();

        $user->loadCount('listings');
        return response()->json([
            'message' => 'User updated successfully',
            'user' => $this->formatUserSummary($user),
        ]);
    }

    // Admin: Delete user
    public function deleteUser(User $user)
    {
        // revoke tokens then delete
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    // Helper: format user output consistently
    private function formatUserSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'user_code' => $user->referral_code ?: (string) $user->id,
            'status' => $user->status ?? 'active',
            'registered_at' => optional($user->created_at)->toDateString(),
            'listings_count' => $user->listings_count ?? $user->listings()->count(),
            'role' => $user->role ?? 'user',
        ];
    }


    //create agent code

    public function storeAgent(Request $request)
    {
        $code = UserClient::create([
            'user_id' => request()->user()->id,
            // 'client_code'=>strtoupper(Str::random(10)),
        ]);

        return response()->json([
            'message' => 'Agent code created successfully',
            'data' => $code
        ]);
    }

    //get clients 
    public function allClients(Request $request)
    {
        $user = $request->user();
        $Client = UserClient::where('user_id', $user->id)->with('user')->get();
        return response()->json([
            'message' => 'Clients retrieved successfully',
            'data' => $Client
        ]);
    }


    //create admin otp
    public function createOtp(User $user)
    {
        $otp = rand(100000, 999999);
        $user->otp = $otp;
        $user->save();
        return response()->json(['message' => 'Otp created successfully', 'otp' => $otp]);
    }

    //user verify otp
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string',
        ]);

        $user = User::where('id', $request->user()->id)->first();
        if ($user->otp != $request->otp) {
            return response()->json(['message' => 'Invalid otp'], 401);
        }
        $user->otp_verified = true;
        $user->save();
        return response()->json(['message' => 'Otp verified successfully']);
    }
}
