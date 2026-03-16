<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DashboardAccountController extends Controller
{
    public function me(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'user' => $this->transformDashboardUser($user),
            'available_dashboard_pages' => $this->availablePages(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone,' . $user->id],
            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_profile_image' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_profile_image') && $user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
            $user->profile_image = null;
        }

        if ($request->hasFile('profile_image')) {
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $user->profile_image = $request->file('profile_image')->store('uploads/profiles', 'public');
        }

        $user->name = $data['name'];
        $user->email = $data['email'] ?? null;
        $user->phone = $data['phone'];
        $user->save();

        return response()->json([
            'message' => 'تم تحديث الحساب الشخصي بنجاح.',
            'user' => $this->transformDashboardUser($user->fresh()),
            'available_dashboard_pages' => $this->availablePages(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور الحالية غير صحيحة.',
                'errors' => [
                    'current_password' => ['كلمة المرور الحالية غير صحيحة.'],
                ],
            ], 422);
        }

        if (Hash::check($data['new_password'], $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور الجديدة يجب أن تختلف عن الحالية.',
                'errors' => [
                    'new_password' => ['كلمة المرور الجديدة يجب أن تختلف عن الحالية.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($data['new_password']);
        $user->save();

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح.',
        ]);
    }

    private function transformDashboardUser(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'status' => $user->status ?? 'active',
            'profile_image_url' => $user->profile_image_url,
            'allowed_dashboard_pages' => $user->dashboardPageKeys(),
        ];
    }

    private function availablePages(): array
    {
        $pages = config('dashboard_permissions', []);

        return collect($pages)
            ->map(fn (array $page, string $key) => [
                'key' => $key,
                'label' => $page['label'] ?? $key,
                'path' => $page['path'] ?? null,
            ])
            ->values()
            ->all();
    }
}
