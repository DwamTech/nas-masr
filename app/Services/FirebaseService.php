<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)->withServiceAccount(config('firebase.credentials'));
            $this->messaging = $factory->createMessaging();
        } catch (\Throwable $e) {
            Log::error('Firebase initialization failed', ['error' => $e->getMessage()]);
            $this->messaging = null;
        }
    }

    /**
     * إرسال إشعار لمستخدم واحد
     */
    public function sendToUser(string $fcmToken, string $title, string $body, ?array $data = null): bool
    {
        if (!$this->messaging || !$fcmToken) {
            return false;
        }

        try {
            $notification = FcmNotification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $this->messaging->send($message);
            
            Log::info('FCM sent successfully', ['token' => substr($fcmToken, 0, 20) . '...']);
            return true;

        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            Log::warning('FCM token not found (probably uninstalled)', ['token' => substr($fcmToken, 0, 20)]);
            return false;
        } catch (\Throwable $e) {
            Log::error('FCM send failed', [
                'error' => $e->getMessage(),
                'token' => substr($fcmToken, 0, 20) . '...'
            ]);
            return false;
        }
    }

    /**
     * إرسال لعدة مستخدمين
     */
    public function sendToMultiple(array $fcmTokens, string $title, string $body, ?array $data = null): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
        ];

        foreach ($fcmTokens as $token) {
            if ($this->sendToUser($token, $title, $body, $data)) {
                $results['success']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * إرسال لجميع المستخدمين (Topic-based)
     */
    public function sendToTopic(string $topic, string $title, string $body, ?array $data = null): bool
    {
        if (!$this->messaging) {
            return false;
        }

        try {
            $notification = FcmNotification::create($title, $body);
            
            $message = CloudMessage::withTarget('topic', $topic)
                ->withNotification($notification);

            if ($data) {
                $message = $message->withData($data);
            }

            $this->messaging->send($message);
            
            Log::info('FCM topic notification sent', ['topic' => $topic]);
            return true;

        } catch (\Throwable $e) {
            Log::error('FCM topic send failed', [
                'error' => $e->getMessage(),
                'topic' => $topic
            ]);
            return false;
        }
    }
}
