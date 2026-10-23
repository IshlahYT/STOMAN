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
     *
     * Parameters:
     *  - $redirectTo (optional route name): when given, non-admin users are
     *    redirected to that route instead of receiving a 403. This is used to
     *    strictly confine regular users to the Home (Dashboard) page.
     */
    public function handle(Request $request, Closure $next, ?string $redirectTo = null): Response
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
            // Regular users are strictly limited to the Home (Dashboard) page.
            if ($redirectTo !== null) {
                return redirect()->route($redirectTo);
            }

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
