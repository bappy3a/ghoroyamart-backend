<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Support\BackendPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $users = User::with('roles')
            ->withCount('permissions')
            ->whereIn('user_type', ['admin','staff'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'user' => new User(),
            'roles' => $this->roles(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($this->payloadFrom($request));
        $this->syncRoles($request, $user);
        $this->syncPermissions($request, $user);

        flash_message('User created successfully!');

        return redirect()->route('users.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load(['roles', 'permissions']),
            'roles' => $this->roles(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($this->payloadFrom($request, $user));
        $this->syncRoles($request, $user);
        $this->syncPermissions($request, $user);

        flash_message('User updated successfully!');

        return redirect()->route('users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        flash_message('User deleted successfully!');

        return redirect()->route('users.index');
    }

    protected function payloadFrom(UserRequest $request, ?User $user = null): array
    {
        $data = $request->validated();
        unset($data['roles'], $data['permissions']);

        // Handle password
        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Handle status
        $data['status'] = $request->input('status', 'active');

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $url = upload_webp_image($request->file('avatar'), 'uploads/users/avatars', 75);
            $data['avatar'] = $url;
        } else {
            unset($data['avatar']);
        }

        // Handle cover photo upload
        if ($request->hasFile('cover_photo')) {
            $url = upload_webp_image($request->file('cover_photo'), 'uploads/users/covers', 75);
            $data['cover_photo'] = $url;
        } else {
            unset($data['cover_photo']);
        }

        return $data;
    }

    protected function roles()
    {
        return Role::where('guard_name', 'web')->orderBy('name')->get();
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

    protected function syncRoles(UserRequest $request, User $user): void
    {
        if (! in_array($user->user_type, ['admin', 'staff'], true)) {
            $user->syncRoles([]);

            return;
        }

        $user->syncRoles(
            Role::where('guard_name', 'web')
                ->whereIn('id', $request->input('roles', []))
                ->pluck('name')
                ->all()
        );
    }

    protected function syncPermissions(UserRequest $request, User $user): void
    {
        if (! in_array($user->user_type, ['admin', 'staff'], true)) {
            $user->syncPermissions([]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        $user->syncPermissions(
            Permission::where('guard_name', 'web')
                ->whereIn('id', $request->input('permissions', []))
                ->pluck('name')
                ->all()
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
