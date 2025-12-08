<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendBulkMessageJob;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    /**
     * Send a broadcast message to multiple users.
     * POST /api/admin/broadcast
     * 
     * The message is dispatched to a queue to avoid timeout.
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'title' => ['nullable', 'string', 'max:255'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'send_to_all' => ['nullable', 'boolean'],
            'filters' => ['nullable', 'array'],
            'filters.status' => ['nullable', 'string'],
            'filters.role' => ['nullable', 'string'],
        ], [
            'message.required' => 'يجب كتابة الرسالة',
            'message.max' => 'الرسالة طويلة جداً (الحد الأقصى 5000 حرف)',
        ]);

        $admin = $request->user();
        $userIds = [];

        // Determine recipients
        if (!empty($data['send_to_all'])) {
            // Send to all users
            $query = User::query();
            
            // Apply filters if any
            if (!empty($data['filters']['status'])) {
                $query->where('status', $data['filters']['status']);
            }
            if (!empty($data['filters']['role'])) {
                $query->where('role', $data['filters']['role']);
            }

            // Exclude admins from broadcast (optional)
            $query->where('role', '!=', 'admin');
            
            $userIds = $query->pluck('id')->toArray();
        } elseif (!empty($data['user_ids'])) {
            // Send to specific users
            $userIds = $data['user_ids'];
        } else {
            return response()->json([
                'message' => 'يجب تحديد المستلمين (user_ids أو send_to_all)',
            ], 422);
        }

        if (empty($userIds)) {
            return response()->json([
                'message' => 'لا يوجد مستلمين',
            ], 422);
        }

        // Dispatch the job to queue
        SendBulkMessageJob::dispatch(
            $userIds,
            $data['message'],
            $admin->id,
            $data['title'] ?? null
        );

        return response()->json([
            'message' => 'تم إرسال الرسالة للمعالجة',
            'data' => [
                'recipients_count' => count($userIds),
                'admin_id' => $admin->id,
                'status' => 'queued',
            ],
        ], 202);
    }

    /**
     * Send broadcast to specific user groups.
     * POST /api/admin/broadcast/group
     */
    public function sendToGroup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'group' => ['required', 'string', 'in:active,inactive,new,premium'],
        ]);

        $admin = $request->user();
        $query = User::where('role', '!=', 'admin');

        // Apply group filter
        switch ($data['group']) {
            case 'active':
                // Users who logged in within last 30 days
                $query->where('updated_at', '>=', now()->subDays(30));
                break;
            case 'inactive':
                // Users who haven't logged in for 30 days
                $query->where('updated_at', '<', now()->subDays(30));
                break;
            case 'new':
                // Users registered in last 7 days
                $query->where('created_at', '>=', now()->subDays(7));
                break;
            case 'premium':
                // Users with active subscriptions (you may need to adjust this)
                $query->where('status', 'active');
                break;
        }

        $userIds = $query->pluck('id')->toArray();

        if (empty($userIds)) {
            return response()->json([
                'message' => 'لا يوجد مستخدمين في هذه المجموعة',
            ], 422);
        }

        SendBulkMessageJob::dispatch(
            $userIds,
            $data['message'],
            $admin->id
        );

        return response()->json([
            'message' => 'تم إرسال الرسالة للمعالجة',
            'data' => [
                'group' => $data['group'],
                'recipients_count' => count($userIds),
                'status' => 'queued',
            ],
        ], 202);
    }

    /**
     * Get broadcast history.
     * GET /api/admin/broadcast/history
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);

        // Get unique broadcast conversations
        $broadcasts = \App\Models\UserConversation::broadcast()
            ->selectRaw('
                conversation_id,
                sender_id,
                message,
                MIN(created_at) as sent_at,
                COUNT(DISTINCT receiver_id) as recipients_count
            ')
            ->groupBy('conversation_id', 'sender_id', 'message')
            ->orderByDesc('sent_at')
            ->paginate($perPage);

        // Enrich with sender info
        $data = collect($broadcasts->items())->map(function ($broadcast) {
            $sender = User::find($broadcast->sender_id);
            
            return [
                'conversation_id' => $broadcast->conversation_id,
                'message' => $broadcast->message,
                'sent_at' => $broadcast->sent_at,
                'recipients_count' => $broadcast->recipients_count,
                'sent_by' => $sender ? [
                    'id' => $sender->id,
                    'name' => $sender->name,
                ] : null,
            ];
        });

        return response()->json([
            'meta' => [
                'page' => $broadcasts->currentPage(),
                'per_page' => $broadcasts->perPage(),
                'total' => $broadcasts->total(),
                'last_page' => $broadcasts->lastPage(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * Preview recipients before sending.
     * POST /api/admin/broadcast/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_ids' => ['nullable', 'array'],
            'send_to_all' => ['nullable', 'boolean'],
            'filters' => ['nullable', 'array'],
        ]);

        $count = 0;

        if (!empty($data['send_to_all'])) {
            $query = User::where('role', '!=', 'admin');
            
            if (!empty($data['filters']['status'])) {
                $query->where('status', $data['filters']['status']);
            }
            
            $count = $query->count();
        } elseif (!empty($data['user_ids'])) {
            $count = User::whereIn('id', $data['user_ids'])->count();
        }

        return response()->json([
            'recipients_count' => $count,
        ]);
    }
}
