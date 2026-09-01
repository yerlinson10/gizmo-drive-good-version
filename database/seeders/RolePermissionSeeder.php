<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app()[PermissionRegistrar::class];
        $registrar->forgetCachedPermissions();

        $permissions = collect([
            'drive.manage',
            'drive.share',
        ])->map(
            fn (string $name) => Permission::findOrCreate($name, 'web'),
        );

        $registrar->forgetCachedPermissions();

        Role::findOrCreate('user', 'web')->syncPermissions($permissions);
        Role::findOrCreate('admin', 'web')->syncPermissions(Permission::all());
    }
}
