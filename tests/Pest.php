<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

use App\Features\UserManagement\Enums\Permission;
use App\Features\UserManagement\Enums\Role;
use App\Models\User;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * Seed the UserManagement roles and permissions used across feature tests.
 */
function seedUserRolesAndPermissions(): void
{
    foreach (Permission::cases() as $permission) {
        PermissionModel::findOrCreate($permission->value, 'web');
    }

    $superAdminRole = RoleModel::findOrCreate(Role::SuperAdmin->value, 'web');
    $superAdminRole->givePermissionTo(PermissionModel::all());
    RoleModel::findOrCreate(Role::User->value, 'web');
}

function createAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(Role::SuperAdmin);

    return $admin;
}

function createRegularUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::User);

    return $user;
}
