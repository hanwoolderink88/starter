<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\UserManagement\Enums\Permission;
use App\Features\UserManagement\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (Permission::cases() as $permission) {
            PermissionModel::findOrCreate($permission->value, 'web');
        }

        $superAdminRole = RoleModel::findOrCreate(Role::SuperAdmin->value, 'web');
        $superAdminRole->givePermissionTo(PermissionModel::all());

        RoleModel::findOrCreate(Role::User->value, 'web');
    }
}
