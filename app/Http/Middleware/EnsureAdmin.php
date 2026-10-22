<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * Handle an incoming request.
     * Restricts access to users with Admin role (id: 1).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user() ?? auth('web')->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->guest(route('login'));
        }

        if (! $user->isAdmin()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. Admin privileges required.',
                ], 403);
            }

            abort(403, 'Forbidden. Admin privileges required.');
        }

        return $next($request);
    }
}
