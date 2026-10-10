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

use App\Features\Roles\Actions\SeedDefaultRolesAction;
use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;

/**
 * Seed the permissions and the default roles (Administrator and User) used
 * across feature tests.
 */
function seedUserRolesAndPermissions(): void
{
    app(SeedDefaultRolesAction::class)->handle(ChangeContext::system());
}

function administratorRole(): Role
{
    return Role::query()->withoutGlobalScope(Role::ACTIVE_SCOPE)->where('is_protected', true)->sole();
}

function createAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole(administratorRole());

    return $admin;
}

function userRole(): Role
{
    return Role::query()->withoutGlobalScope(Role::ACTIVE_SCOPE)->where('name', SeedDefaultRolesAction::USER_NAME)->sole();
}

function createRegularUser(): User
{
    $user = User::factory()->create();
    $user->assignRole(userRole());

    return $user;
}

/**
 * A user whose only role grants exactly these permissions.
 */
function userWithPermissions(Permission ...$permissions): User
{
    $user = User::factory()->create();
    $user->assignRole(Role::factory()->withPermissions($permissions)->create());

    return $user;
}
