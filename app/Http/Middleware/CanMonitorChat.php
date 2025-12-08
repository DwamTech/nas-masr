<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CanMonitorChat
{
    /**
     * Handle an incoming request.
     *
     * Checks if the admin has permission to monitor peer-to-peer chats.
     * All access attempts are logged for accountability.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Must be authenticated
        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح',
            ], 401);
        }

        // Must be admin
        if (!$user->isAdmin()) {
            $this->logUnauthorizedAccess($user, 'not_admin');
            
            return response()->json([
                'message' => 'غير مصرح - يجب أن تكون مسؤول',
            ], 403);
        }

        // Optional: Check for specific permission if using Spatie or similar
        // if (!$user->can('monitor_chat')) {
        //     $this->logUnauthorizedAccess($user, 'missing_permission');
        //     
        //     return response()->json([
        //         'message' => 'غير مصرح - ليس لديك صلاحية مراقبة المحادثات',
        //     ], 403);
        // }

        // Log the access attempt (successful)
        $this->logAccessAttempt($user);

        return $next($request);
    }

    /**
     * Log successful access attempt.
     */
    private function logAccessAttempt($user): void
    {
        Log::channel('daily')->info('Chat Monitoring Access Attempt', [
            'admin_id' => $user->id,
            'admin_name' => $user->name,
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Log unauthorized access attempt.
     */
    private function logUnauthorizedAccess($user, string $reason): void
    {
        Log::channel('daily')->warning('Unauthorized Chat Monitoring Access Attempt', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'reason' => $reason,
            'endpoint' => request()->path(),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
