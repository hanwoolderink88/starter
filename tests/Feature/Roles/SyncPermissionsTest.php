<?php

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Infrastructure\Activity\Models\Activity;
use Spatie\Permission\Models\Permission as PermissionModel;

test('the seeder creates the permissions, the administrator and a user role', function () {
    seedUserRolesAndPermissions();

    expect(PermissionModel::query()->pluck('name')->sort()->values()->all())
        ->toBe(collect(Permission::cases())->pluck('value')->sort()->values()->all())
        ->and(administratorRole()->permissions)->toHaveCount(count(Permission::cases()))
        ->and(userRole()->permissions)->toHaveCount(0);
});

test('syncing is idempotent and only logs real changes', function () {
    seedUserRolesAndPermissions();
    $entries = Activity::query()->count();

    $this->artisan('permissions:sync')->assertSuccessful();
    seedUserRolesAndPermissions();

    expect(Activity::query()->count())->toBe($entries);
});

test('a permission that is new in the catalogue goes to the administrator', function () {
    seedUserRolesAndPermissions();
    administratorRole()->revokePermissionTo(Permission::ViewActivity);
    PermissionModel::query()->where('name', Permission::ViewActivity->value)->delete();

    $this->artisan('permissions:sync')->assertSuccessful();

    expect(administratorRole()->fresh()->hasPermissionTo(Permission::ViewActivity))->toBeTrue()
        ->and(Activity::query()->latest('id')->first()->changes['permissions']['new'])->toContain('view activity');
});

test('a deactivated user role is not recreated by the seeder', function () {
    seedUserRolesAndPermissions();
    userRole()->update(['is_active' => false]);

    seedUserRolesAndPermissions();

    expect(Role::query()->withoutGlobalScope(Role::ACTIVE_SCOPE)->where('name', 'User')->count())->toBe(1)
        ->and(userRole()->is_active)->toBeFalse();
});
