<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BackendPermission;
use Illuminate\Contracts\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(): View
    {
        $permissionGroups = Permission::withCount('roles')
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->headline()->toString())
            ->map(fn ($permissions) => $permissions->sortBy(function (Permission $permission) {
                $action = str($permission->name)->after('.')->toString();

                return array_search($action, BackendPermission::ACTIONS, true);
            }));

        return view('admin.permissions.index', compact('permissionGroups'));
    }
}
