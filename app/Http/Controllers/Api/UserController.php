<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\User;
use GuzzleHttp\Psr7\Message;
use Illuminate\Support\Facades\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    //

    public function getUserProfile()
    {
        $user = Auth::user();

        if (!$user) {
            return response([
                'message' => 'User not authenticated'
            ], 401);
        }

        return response([
            'message' => 'Profile fetched successfully',
            'data' => $user
        ], 200);
    }



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

        $perPage = $request->query('per_page');
        $statusFilter = $request->query('status'); // Optional: Valid/Pending/Rejected/Expired
        $all = filter_var($request->query('all', false), FILTER_VALIDATE_BOOLEAN);

        $query = Listing::query()
            ->leftJoin('categories', 'listings.category_id', '=', 'categories.id')
            ->where('listings.user_id', $user->id)
            ->when($statusFilter, fn($q) => $q->where('listings.status', $statusFilter))
            ->select([
                'listings.id',
                'listings.title',
                'listings.main_image',
                'listings.status',
                'listings.published_at',
                'categories.name as category_name',
            ])
            ->orderByDesc('listings.published_at')
            ->orderByDesc('listings.created_at');

        $mapStatus = function ($status) {
            return match ($status) {
                'Valid' => 'منشور',
                'Pending' => 'قيد المراجعة',
                'Rejected' => 'مرفوض',
                'Expired' => 'منتهي',
                default => $status,
            };
        };

        if ($all && !$perPage) {
            $rows = $query->get();
            $items = $rows->map(function ($row) use ($mapStatus) {
                return [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'image' => $row['main_image'],
                    'section' => $row['category_name'],
                    'status' => $mapStatus($row['status']),
                    'published_at' => $row['published_at'] ? (string) $row['published_at'] : null,
                ];
            })->values();

            return response()->json([
                'user' => $this->formatUserSummary($user),
                'listings' => $items,
                'meta' => ['total' => count($items)],
            ]);
        }

        $perPage = (int) ($perPage ?? 20);
        $listings = $query->paginate($perPage);
        $items = collect($listings->items())->map(function ($row) use ($mapStatus) {
            return [
                'id' => $row['id'],
                'title' => $row['title'],
                'image' => $row['main_image'],
                'section' => $row['category_name'],
                'status' => $mapStatus($row['status']),
                'published_at' => $row['published_at'] ? (string) $row['published_at'] : null,
            ];
        })->values();

        return response()->json([
            'user' => $this->formatUserSummary($user),
            'listings' => $items,
            'meta' => [
                'page' => $listings->currentPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'last_page' => $listings->lastPage(),
            ],
        ]);
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
}
