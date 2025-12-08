<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\User;
use App\Models\UserConversation;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private ChatService $chatService
    ) {}

    /**
     * Send a message to another user.
     * POST /api/chat/send
     */
    public function send(SendMessageRequest $request): JsonResponse
    {
        $sender = $request->user();
        $receiver = User::findOrFail($request->validated('receiver_id'));
        $message = $request->validated('message');

        // Prevent sending message to self
        if ($sender->id === $receiver->id) {
            return response()->json([
                'message' => 'لا يمكنك إرسال رسالة لنفسك',
            ], 422);
        }

        $conversation = $this->chatService->sendMessage(
            $sender,
            $receiver,
            $message,
            UserConversation::TYPE_PEER
        );

        return response()->json([
            'message' => 'تم إرسال الرسالة بنجاح',
            'data' => [
                'id' => $conversation->id,
                'conversation_id' => $conversation->conversation_id,
                'message' => $conversation->message,
                'created_at' => $conversation->created_at,
            ],
        ], 201);
    }

    /**
     * Get conversation history with a specific user.
     * GET /api/chat/{user}
     */
    public function history(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        // Prevent viewing own conversation
        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'غير صالح',
            ], 422);
        }

        $perPage = (int) $request->query('per_page', 50);
        $messages = $this->chatService->getHistoryBetweenUsers($currentUser, $user, $perPage);

        // Mark messages as read
        $conversationId = $this->chatService->getConversationId($currentUser, $user);
        $this->chatService->markConversationAsRead($conversationId, $currentUser);

        return response()->json([
            'meta' => [
                'conversation_id' => $conversationId,
                'page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
            ],
            'data' => $messages->items(),
        ]);
    }

    /**
     * Get user's inbox (list of conversations).
     * GET /api/chat/inbox
     */
    public function inbox(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->query('type'); // Optional: filter by type

        $conversations = $this->chatService->getInbox($user, $type);

        return response()->json([
            'data' => $conversations,
            'unread_total' => $this->chatService->getTotalUnreadCount($user),
        ]);
    }

    /**
     * Send a message to support.
     * POST /api/chat/support
     */
    public function sendToSupport(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $sender = $request->user();
        $message = $request->input('message');

        $conversation = $this->chatService->sendSupportMessage($sender, $message);

        return response()->json([
            'message' => 'تم إرسال رسالتك للدعم الفني بنجاح',
            'data' => [
                'id' => $conversation->id,
                'conversation_id' => $conversation->conversation_id,
                'message' => $conversation->message,
                'created_at' => $conversation->created_at,
            ],
        ], 201);
    }

    /**
     * Get support conversation history.
     * GET /api/chat/support
     */
    public function supportHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $conversationId = $this->chatService->getConversationId($user, null, UserConversation::TYPE_SUPPORT);

        $perPage = (int) $request->query('per_page', 50);
        $messages = $this->chatService->getHistory($conversationId, $perPage);

        // Mark as read
        $this->chatService->markConversationAsRead($conversationId, $user);

        return response()->json([
            'meta' => [
                'conversation_id' => $conversationId,
                'page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
            ],
            'data' => $messages->items(),
        ]);
    }

    /**
     * Mark a conversation as read.
     * PATCH /api/chat/{conversationId}/read
     */
    public function markAsRead(Request $request, string $conversationId): JsonResponse
    {
        $user = $request->user();
        $count = $this->chatService->markConversationAsRead($conversationId, $user);

        return response()->json([
            'message' => 'ok',
            'marked_count' => $count,
        ]);
    }

    /**
     * Get unread messages count.
     * GET /api/chat/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'unread_count' => $this->chatService->getTotalUnreadCount($user),
        ]);
    }
}
