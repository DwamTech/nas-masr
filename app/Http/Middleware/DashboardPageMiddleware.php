<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardPageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$pageKeys): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'غير مصرح',
            ], 401);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $pageKeys = array_values(array_unique(array_filter(array_map(
            static fn (string $value) => trim($value),
            $pageKeys
        ))));

        if ($pageKeys === []) {
            return $next($request);
        }

        foreach ($pageKeys as $pageKey) {
            if ($user->hasDashboardPage($pageKey)) {
                return $next($request);
            }
        }

        return response()->json([
            'message' => 'ليس لديك صلاحية الوصول إلى هذه الصفحة.',
            'required_page_keys' => $pageKeys,
        ], 403);
    }
}
