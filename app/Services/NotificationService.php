<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    public function dispatch(int $userId, string $title, string $body, ?string $type = null, ?array $data = null): array
    {
        $user = User::findOrFail($userId);

        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);

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

        return ['notification' => $notification, 'external_sent' => $externalSent];
    }

    protected function sendExternal(User $user, array $payload): bool
    {
        return true;
    }
}

