<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Cooldown period in seconds for duplicate notifications
     */
    private const COOLDOWN_SECONDS = 120; // 2 minutes

    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function dispatch(int $userId, string $title, string $body, ?string $type = null, ?array $data = null, bool $bypassCooldown = false): array
    {
        $user = User::findOrFail($userId);

        // ✅ إشعارات الأدمن: لا قيود، لا cooldown، لا شيء - تنفيذ فوري
        if ($type === 'الاداره' || $bypassCooldown) {
            Log::info('🔵 Admin notification bypass activated', [
                'user_id' => $userId,
                'title' => $title,
                'bypass_flag' => $bypassCooldown
            ]);

            // Create internal notification
            $notification = Notification::create([
                'user_id' => $user->id,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => $data,
            ]);

            // Check if external notification should be sent
            $globalEnabled = Cache::remember('settings:enable_global_external_notif', now()->addHours(6), function () {
                $val = SystemSetting::where('key', 'enable_global_external_notif')->value('value');
                return (string) $val === '1';
            });

            $shouldSendExternal = $globalEnabled && (bool) $user->receive_external_notif;

            Log::info('🔍 External notification check', [
                'global_enabled' => $globalEnabled,
                'user_setting' => (bool) $user->receive_external_notif,
                'should_send' => $shouldSendExternal,
                'has_token' => !empty($user->fcm_token),
            ]);

            $externalSent = false;
            if ($shouldSendExternal) {
                $externalSent = $this->sendExternal($user, [
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => $data,
                ]);
                
                Log::info('📤 External notification result', [
                    'sent' => $externalSent,
                    'user_id' => $userId,
                ]);
            }

            return [
                'notification' => $notification,
                'external_sent' => $externalSent,
                'skipped' => false,
                'admin_bypass' => true, // ✅ علامة توضح أن الإشعار من الأدمن
            ];
        }

        // ⏱️ الإشعارات العادية: تخضع للـ cooldown
        // Build cache key with type and listing_id for per-listing rate limiting
        $listingId = $data['listing_id'] ?? null;
        $cacheKeySuffix = $type ? ":{$type}" : '';
        $cacheKeySuffix .= $listingId ? ":{$listingId}" : '';
        $cacheKey = "notif:cooldown:{$user->id}{$cacheKeySuffix}";

        // Check cooldown
        $lastSent = Cache::get($cacheKey);
        $nowTs = now()->timestamp;
        
        if ($lastSent && ($nowTs - (int) $lastSent) < self::COOLDOWN_SECONDS) {
            // Within cooldown period - skip notification entirely
            return [
                'notification' => null,
                'external_sent' => false,
                'skipped' => true,
                'cooldown_remaining' => self::COOLDOWN_SECONDS - ($nowTs - (int) $lastSent),
            ];
        }

        // Cooldown passed - create internal notification
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);

        // Update cooldown timestamp
        Cache::put($cacheKey, $nowTs, now()->addSeconds(self::COOLDOWN_SECONDS));

        // Check if external notification should be sent
        $globalEnabled = Cache::remember('settings:enable_global_external_notif', now()->addHours(6), function () {
            $val = SystemSetting::where('key', 'enable_global_external_notif')->value('value');
            return (string) $val === '1';
        });

        $shouldSendExternal = $globalEnabled && (bool) $user->receive_external_notif;

        $externalSent = false;
        if ($shouldSendExternal) {
            $externalSent = $this->sendExternal($user, [
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => $data,
            ]);
        }

        return [
            'notification' => $notification,
            'external_sent' => $externalSent,
            'skipped' => false,
        ];
    }

    protected function sendExternal(User $user, array $payload): bool
    {
        if (!$user->fcm_token) {
            Log::info('User has no FCM token', ['user_id' => $user->id]);
            return false;
        }

        return $this->firebase->sendToUser(
            $user->fcm_token,
            $payload['title'],
            $payload['body'],
            $payload['data'] ?? null
        );
    }
}
