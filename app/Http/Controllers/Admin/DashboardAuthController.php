<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DashboardAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $identifier = trim((string) $data['identifier']);

        $user = User::query()
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('phone', $identifier);
            })
            ->first();

        if (!$user || !$user->canAccessDashboard() || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => ['بيانات الدخول غير صحيحة أو الحساب غير مصرح له بالداشبورد.'],
            ]);
        }

        if (($user->status ?? 'active') !== 'active') {
            return response()->json([
                'message' => 'الحساب غير نشط.',
            ], 403);
        }

        $token = $user->createToken('dashboard_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'token' => $token,
            'user' => $this->transformDashboardUser($user),
            'available_dashboard_pages' => $this->availablePages(),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'تم تسجيل الخروج بنجاح.',
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
