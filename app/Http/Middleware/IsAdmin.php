<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    /**
     * Ensure the authenticated user is an admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->user_type, ['admin', 'staff'], true)) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized.');
        }

        return $next($request);
    }
}
