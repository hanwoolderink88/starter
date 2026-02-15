<?php

use App\Features\UserManagement\Enums\Permission;
use App\Features\UserManagement\Enums\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

beforeEach(function () {
    foreach (Permission::cases() as $permission) {
        PermissionModel::findOrCreate($permission->value, 'web');
    }

    $superAdminRole = RoleModel::findOrCreate(Role::SuperAdmin->value, 'web');
    $superAdminRole->givePermissionTo(PermissionModel::all());
    RoleModel::findOrCreate(Role::User->value, 'web');
});

test('admins can impersonate another user', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::SuperAdmin);
    $target = User::factory()->create();
    $target->assignRole(Role::User);

    $this->actingAs($admin);

    $this->post(route('users.impersonate', $target))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($target);
});

test('admins cannot impersonate themselves', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Role::SuperAdmin);

    $this->actingAs($admin);

    $this->post(route('users.impersonate', $admin))->assertForbidden();
});

test('regular users cannot impersonate', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::User);
    $target = User::factory()->create();

    $this->actingAs($user);

    $this->post(route('users.impersonate', $target))->assertForbidden();
});

test('guests cannot impersonate', function () {
    $target = User::factory()->create();

    $this->post(route('users.impersonate', $target))
        ->assertRedirect(route('login'));
});
