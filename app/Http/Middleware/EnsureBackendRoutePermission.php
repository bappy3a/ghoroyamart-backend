<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\BackendPermission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBackendRoutePermission
{
    /**
     * Match backend access to the current section action permission.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $permission = BackendPermission::fromRoute($request->route());

        if (! $user || ! $permission) {
            return $next($request);
        }

        if ($this->isPrimaryAdmin($user) || $user->hasRole('Super Admin') || $user->can($permission)) {
            return $next($request);
        }

        // Editor media upload is used on both create and edit product forms.
        if ($permission === 'products.create' && $user->can('products.update') && str_contains((string) $request->route()?->getName(), 'editor-upload')) {
            return $next($request);
        }

        abort(Response::HTTP_FORBIDDEN, 'You do not have permission to access this page.');
    }

    protected function isPrimaryAdmin(User $user): bool
    {
        return $user->user_type === 'admin'
            && User::where('user_type', 'admin')->orderBy('id')->value('id') === $user->id;
    }
}
