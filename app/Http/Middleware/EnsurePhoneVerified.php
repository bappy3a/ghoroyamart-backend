<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    /**
     * Handle an incoming request.
     * Redirect to phone verification page if phone is not verified.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is not authenticated, let them through (auth middleware will handle it)
        if (!$user) {
            return $next($request);
        }

        // Check if phone is verified
        if ($user->user_type == 'user' && !$user->phone_verified_at) {
            // Allow access to verification routes
            $routeName = $request->route()->getName();
            if (in_array($routeName, ['customer.phone.verify', 'customer.phone.send-code'])) {
                return $next($request);
            }

            // Redirect to phone verification page
            return redirect()->route('customer.phone.verify');
        }
        return $next($request);
    }
}
