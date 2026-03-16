<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح',
            ], 401);
        }

        if (!$user->canAccessDashboard()) {
            return response()->json([
                'message' => 'غير مصرح بدخول لوحة التحكم.',
            ], 403);
        }

        return $next($request);
    }
}
