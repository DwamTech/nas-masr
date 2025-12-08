<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    /**
     * Get all peer-to-peer conversations for monitoring.
     * GET /api/admin/monitoring/conversations
     * 
     * READ-ONLY: Admins can only view, not write.
     * All access is logged for accountability.
     */
    public function index(Request $request): JsonResponse
    {
        $admin = $request->user();
        
        // Log the monitoring access
        $this->logAccess($admin, 'viewed_conversations_list');

        $perPage = (int) $request->query('per_page', 20);
        $search = $request->query('search'); // Search by user name/phone

        // Get all peer conversations with participants
        $query = UserConversation::peer()
            ->selectRaw('
                conversation_id,
                MIN(created_at) as started_at,
                MAX(created_at) as last_message_at,
                COUNT(*) as messages_count
            ')
            ->groupBy('conversation_id')
            ->orderByDesc('last_message_at');

        $conversations = $query->paginate($perPage);

        // Enrich with participant details
        $data = collect($conversations->items())->map(function ($conv) {
            $participants = $this->getConversationParticipants($conv->conversation_id);

            return [
                'conversation_id' => $conv->conversation_id,
                'participants' => $participants,
                'started_at' => $conv->started_at,
                'last_message_at' => $conv->last_message_at,
                'messages_count' => $conv->messages_count,
            ];
        });

        return response()->json([
            'meta' => [
                'page' => $conversations->currentPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
                'last_page' => $conversations->lastPage(),
            ],
            'data' => $data,
        ]);
    }

    /**
     * View a specific conversation (Read-Only).
     * GET /api/admin/monitoring/conversations/{conversationId}
     */
    public function show(Request $request, string $conversationId): JsonResponse
    {
        $admin = $request->user();
        
        // Log the monitoring access with conversation ID
        $this->logAccess($admin, 'viewed_conversation', [
            'conversation_id' => $conversationId,
        ]);

        $perPage = (int) $request->query('per_page', 50);

        // Check if conversation exists and is peer type
        $exists = UserConversation::inConversation($conversationId)
            ->peer()
            ->exists();

        if (!$exists) {
            return response()->json([
                'message' => 'المحادثة غير موجودة أو ليست من نوع peer',
            ], 404);
        }

        // Get participants
        $participants = $this->getConversationParticipants($conversationId);

        // Get messages
        $messages = UserConversation::inConversation($conversationId)
            ->peer()
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);

        return response()->json([
            'meta' => [
                'conversation_id' => $conversationId,
                'participants' => $participants,
                'page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
            ],
            'data' => $messages->items(),
        ]);
    }

    /**
     * Search conversations by user.
     * GET /api/admin/monitoring/search
     */
    public function search(Request $request): JsonResponse
    {
        $admin = $request->user();
        $query = $request->query('q');

        if (!$query || strlen($query) < 2) {
            return response()->json([
                'message' => 'يجب إدخال كلمة بحث (على الأقل حرفين)',
            ], 422);
        }

        // Log the search
        $this->logAccess($admin, 'searched_conversations', [
            'query' => $query,
        ]);

        // Find users matching the query
        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name', 'phone', 'email']);

        // Get conversations for these users
        $userIds = $users->pluck('id')->toArray();

        $conversations = UserConversation::peer()
            ->where(function ($q) use ($userIds) {
                $q->whereIn('sender_id', $userIds)
                    ->orWhereIn('receiver_id', $userIds);
            })
            ->selectRaw('conversation_id, MAX(created_at) as last_message_at, COUNT(*) as messages_count')
            ->groupBy('conversation_id')
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get();

        $data = $conversations->map(function ($conv) {
            $participants = $this->getConversationParticipants($conv->conversation_id);

            return [
                'conversation_id' => $conv->conversation_id,
                'participants' => $participants,
                'last_message_at' => $conv->last_message_at,
                'messages_count' => $conv->messages_count,
            ];
        });

        return response()->json([
            'users_found' => $users->count(),
            'conversations_found' => $data->count(),
            'data' => $data,
        ]);
    }

    /**
     * Get conversation statistics.
     * GET /api/admin/monitoring/stats
     */
    public function stats(): JsonResponse
    {
        $totalPeerConversations = UserConversation::peer()
            ->distinct('conversation_id')
            ->count('conversation_id');

        $totalPeerMessages = UserConversation::peer()->count();

        $todayMessages = UserConversation::peer()
            ->whereDate('created_at', today())
            ->count();

        $activeUsersToday = UserConversation::peer()
            ->whereDate('created_at', today())
            ->distinct('sender_id')
            ->count('sender_id');

        return response()->json([
            'total_peer_conversations' => $totalPeerConversations,
            'total_peer_messages' => $totalPeerMessages,
            'today_messages' => $todayMessages,
            'active_users_today' => $activeUsersToday,
        ]);
    }

    /**
     * Get participants of a conversation.
     */
    private function getConversationParticipants(string $conversationId): array
    {
        $messages = UserConversation::inConversation($conversationId)
            ->with(['sender', 'receiver'])
            ->limit(10)
            ->get();

        $participants = collect();

        foreach ($messages as $msg) {
            if ($msg->sender) {
                $participants->put($msg->sender->id, [
                    'id' => $msg->sender->id,
                    'name' => $msg->sender->name,
                    'phone' => $msg->sender->phone,
                ]);
            }
            if ($msg->receiver) {
                $participants->put($msg->receiver->id, [
                    'id' => $msg->receiver->id,
                    'name' => $msg->receiver->name,
                    'phone' => $msg->receiver->phone,
                ]);
            }
        }

        return $participants->values()->toArray();
    }

    /**
     * Log monitoring access for accountability.
     */
    private function logAccess(User $admin, string $action, array $context = []): void
    {
        Log::channel('daily')->info('Chat Monitoring Access', [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'action' => $action,
            'context' => $context,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
