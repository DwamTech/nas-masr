<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminNotificationService;
use App\Services\NotificationService;
use App\Models\AdminNotifications;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = AdminNotifications::latest()->paginate(20);

        return response()->json($notifications);
    }

    public function markAsRead($id)
    {
        $notification = AdminNotifications::findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function unreadCount()
    {
        $count = AdminNotifications::whereNull('read_at')->count();

        return response()->json(['count' => $count]);
    }

    public function store(
        Request $request,
        NotificationService $notifications,
        AdminNotificationService $adminNotifications
    ) {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'data' => ['nullable', 'array'],
        ]);

        $result = $notifications->dispatch(
            $data['user_id'],
            $data['title'],
            $data['body'],
            $data['type'] ?? null,
            $data['data'] ?? null,
            true
        );

        $adminLog = $adminNotifications->dispatch(
            $data['title'],
            $data['body'],
            $data['type'] ?? 'system',
            array_merge($data['data'] ?? [], [
                'user_id' => $data['user_id'],
                'notification_id' => $result['notification']?->id,
            ]),
            'dashboard'
        );

        return response()->json([
            'message' => 'created',
            'data' => $result['notification'],
            'external_sent' => $result['external_sent'],
            'admin_notification' => $adminLog['notification'],
        ], 201);
    }
}
