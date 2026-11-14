<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Permissions;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Syncs roles + permissions from App\Support\Permissions. Safe to re-run after adding a module. */
final class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (Permissions::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (Permissions::roles() as $roleName => $resources) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions(Permissions::forResources(
                $resources,
                withForceDelete: $roleName === Permissions::SUPER_ADMIN,
            ));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
