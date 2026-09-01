<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\BackendPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $backendRoutes = collect(Route::getRoutes())
            ->filter(fn ($route) => $route->getName() && str_starts_with($route->uri(), 'backend') && $route->getName() !== 'dashboard');

        $routePermissionMap = $backendRoutes
            ->mapWithKeys(fn ($route) => [$route->getName() => BackendPermission::fromRoute($route)]);

        $permissionNames = $backendRoutes
            ->map(fn ($route) => BackendPermission::sectionFromRouteName($route->getName()))
            ->unique()
            ->sort()
            ->flatMap(fn ($section) => BackendPermission::allForSection($section))
            ->merge($routePermissionMap->values())
            // Keep report permissions available even when production is using a stale route cache.
            ->merge([
                'profit-loss-report.show',
                'moderator-order-report.show',
                'total-order-report.show',
            ])
            ->unique()
            ->sort()
            ->values();

        $permissionNames->each(fn ($name) => Permission::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
        ]));

        $this->renamePermission('orders.show', 'orders.all');

        $routePermissionMap->each(function (string $newPermissionName, string $oldRouteName) {
            if ($oldRouteName === $newPermissionName) {
                return;
            }

            $oldPermission = Permission::where('guard_name', 'web')->where('name', $oldRouteName)->first();
            $newPermission = Permission::where('guard_name', 'web')->where('name', $newPermissionName)->first();

            if (! $oldPermission || ! $newPermission) {
                return;
            }

            $oldPermission->roles()->each(fn (Role $role) => $role->givePermissionTo($newPermission));
        });

        Permission::where('guard_name', 'web')
            ->whereIn('name', $routePermissionMap->keys()->diff($permissionNames)->all())
            ->delete();

        Permission::where('guard_name', 'web')->where('name', 'dashboard')->delete();
        Permission::where('guard_name', 'web')->where('name', 'orders.show')->delete();

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $superAdmin->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());

        $moderatorOrderManagementPermissions = Permission::where('guard_name', 'web')
            ->whereIn('name', [
                'moderator-order-management.show',
                'moderator-order-management.create',
            ])
            ->pluck('name')
            ->all();

        foreach (['Moderator', 'Manager'] as $roleName) {
            Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ])->givePermissionTo($moderatorOrderManagementPermissions);
        }

        $primaryAdmin = User::where('user_type', 'admin')->orderBy('id')->first();

        if ($primaryAdmin && ! $primaryAdmin->hasRole($superAdmin)) {
            $primaryAdmin->assignRole($superAdmin);
        }

        User::where('user_type', 'admin')->each(function (User $user) use ($superAdmin) {
            if (! $user->roles()->exists()) {
                $user->assignRole($superAdmin);
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function renamePermission(string $oldName, string $newName): void
    {
        $oldPermission = Permission::where('guard_name', 'web')->where('name', $oldName)->first();
        $newPermission = Permission::where('guard_name', 'web')->where('name', $newName)->first();

        if (! $oldPermission || ! $newPermission || $oldPermission->id === $newPermission->id) {
            return;
        }

        DB::table('role_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->pluck('role_id')
            ->each(fn ($roleId) => DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $newPermission->id,
                'role_id' => $roleId,
            ]));

        DB::table('model_has_permissions')
            ->where('permission_id', $oldPermission->id)
            ->get()
            ->each(function ($row) use ($newPermission) {
                $record = (array) $row;
                $record['permission_id'] = $newPermission->id;

                DB::table('model_has_permissions')->insertOrIgnore($record);
            });
    }
}
