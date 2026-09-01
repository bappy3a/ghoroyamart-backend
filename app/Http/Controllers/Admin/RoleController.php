<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BackendPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'role' => new Role(['guard_name' => 'web']),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->permissionNames($data['permissions'] ?? []));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        flash_message('Role created successfully!');

        return redirect()->route('roles.index');
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $this->validated($request, $role);

        if ($role->name !== 'Super Admin') {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($this->permissionNames($data['permissions'] ?? []));
        } else {
            $role->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        flash_message('Role updated successfully!');

        return redirect()->route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'Super Admin') {
            flash_message('Super Admin role cannot be deleted.', 'error');

            return redirect()->route('roles.index');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        flash_message('Role deleted successfully!');

        return redirect()->route('roles.index');
    }

    protected function validated(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role?->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'integer',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ]);
    }

    protected function permissionGroups()
    {
        return Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission) => str($permission->name)->before('.')->headline()->toString())
            ->map(fn ($permissions) => $permissions->sortBy(function (Permission $permission) {
                $action = str($permission->name)->after('.')->toString();

                return array_search($action, BackendPermission::ACTIONS, true);
            }));
    }

    protected function permissionNames(array $permissionIds): array
    {
        return Permission::where('guard_name', 'web')
            ->whereIn('id', $permissionIds)
            ->pluck('name')
            ->all();
    }
}
