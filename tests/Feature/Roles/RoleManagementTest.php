<?php

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Events\RoleChanged;
use App\Features\Roles\Models\Role;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('managers see every role, inactive ones included', function () {
    Role::factory()->inactive()->create(['name' => 'Retired']);

    $this->actingAs(createAdmin())->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('roles/index')
            ->has('roles', 3)
            ->where('roles.0.name', 'Administrator')
            ->where('roles.0.isProtected', true)
            ->where('permissionTotal', count(Permission::cases()))
            ->where('inactiveCount', 1));
});

test('without the manage roles permission the role screens are closed', function () {
    $user = userWithPermissions(Permission::ViewUsers, Permission::UpdateUsers);
    $role = userRole();

    $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
    $this->actingAs($user)->get(route('roles.edit', $role))->assertForbidden();
    $this->actingAs($user)->post(route('roles.store'), ['name' => 'Sneaky'])->assertForbidden();
    $this->actingAs($user)->delete(route('roles.destroy', $role))->assertForbidden();
});

test('a role is created with its permissions, logged and signalled', function () {
    Event::fake([RoleChanged::class]);
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('roles.store'), [
        'name' => 'Support',
        'description' => 'Helps users',
        'permissions' => [Permission::ViewUsers->value, Permission::ViewActivity->value],
    ])->assertRedirect(route('roles.index'));

    $role = Role::query()->where('name', 'Support')->sole();

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(['view activity', 'view users'])
        ->and(Activity::query()->forSubject($role)->sole())
        ->event->toBe(ActivityEvent::Created)
        ->causer_id->toBe($admin->id);

    Event::assertDispatched(RoleChanged::class, fn (RoleChanged $event) => $event->payload->id === $role->id);
});

test('role names are unique and permissions must exist', function () {
    $this->actingAs(createAdmin())->post(route('roles.store'), [
        'name' => 'User',
        'permissions' => ['launch rockets'],
    ])->assertSessionHasErrors(['name', 'permissions.0']);
});

test('updating a role replaces its permissions and logs what changed', function () {
    $role = Role::factory()->withPermissions([Permission::ViewUsers])->create(['name' => 'Support']);

    // Name and description save per field (collaboration); Save ignores them.
    $this->actingAs(createAdmin())->put(route('roles.update', $role), ['version' => $role->fresh()->version,
        'name' => 'Helpdesk',
        'permissions' => [Permission::ViewActivity->value],
    ])->assertRedirect(route('roles.index'));

    expect($role->fresh())
        ->name->toBe('Support')
        ->and($role->fresh()->permissions->pluck('name')->all())->toBe(['view activity'])
        ->and(Activity::query()->forSubject($role)->sole()->changes)->toBe([
            'permissions' => ['old' => ['view users'], 'new' => ['view activity']],
        ]);
});

test('the administrator role always keeps every permission', function () {
    $administrator = administratorRole();

    $this->actingAs(createAdmin())->put(route('roles.update', $administrator), ['version' => $administrator->fresh()->version,
        'permissions' => [],
    ])->assertRedirect(route('roles.index'));

    expect($administrator->fresh()->permissions)->toHaveCount(count(Permission::cases()));
});

test('the administrator role can be neither deactivated nor deleted', function () {
    $admin = createAdmin();
    $administrator = administratorRole();

    $this->actingAs($admin)->post(route('roles.toggle-active', $administrator))->assertForbidden();
    $this->actingAs($admin)->delete(route('roles.destroy', $administrator))->assertForbidden();

    expect($administrator->fresh()->is_active)->toBeTrue();
});

test('an inactive role grants nothing but keeps its assignments', function () {
    $role = Role::factory()->withPermissions([Permission::ViewUsers])->create();
    $user = User::factory()->create();
    $user->assignRole($role);

    expect($user->fresh()->can(Permission::ViewUsers->value))->toBeTrue();

    $this->actingAs(createAdmin())->post(route('roles.toggle-active', $role))->assertRedirect();

    expect($user->fresh()->can(Permission::ViewUsers->value))->toBeFalse()
        ->and($user->roles()->withoutGlobalScope(Role::ACTIVE_SCOPE)->pluck('id')->all())->toBe([$role->id])
        ->and(Activity::query()->forSubject($role)->sole())
        ->event->toBe(ActivityEvent::StatusChanged)
        ->changes->toBe(['is_active' => ['old' => true, 'new' => false]]);

    $this->post(route('roles.toggle-active', $role));

    expect($user->fresh()->can(Permission::ViewUsers->value))->toBeTrue();
});

test('an inactive role can still be opened to edit or reactivate', function () {
    $role = Role::factory()->inactive()->create();

    $this->actingAs(createAdmin())->get(route('roles.edit', $role))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('role.isActive', false));
});

test('a role that someone still has cannot be deleted', function () {
    $role = Role::factory()->create();
    $holder = User::factory()->create();
    $holder->assignRole($role);

    $this->actingAs(createAdmin())->delete(route('roles.destroy', $role))
        ->assertSessionHasErrors('role');

    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue();
});

test('a free role is deleted with a snapshot in the log', function () {
    $role = Role::factory()->withPermissions([Permission::ViewUsers])->create(['name' => 'Temp']);

    $this->actingAs(createAdmin())->delete(route('roles.destroy', $role))
        ->assertRedirect(route('roles.index'));

    expect(Role::query()->withoutGlobalScope(Role::ACTIVE_SCOPE)->whereKey($role->id)->exists())->toBeFalse()
        ->and(Activity::query()->where('event', ActivityEvent::Deleted)->where('subject_type', 'role')->sole()->changes['snapshot'])
        ->toMatchArray(['name' => 'Temp', 'permissions' => ['view users']]);
});

test('the role form leaves the viewer\'s own shared permissions alone', function () {
    $this->actingAs(createAdmin())->get(route('roles.edit', userRole()))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rolePermissions', [])
            ->has('permissions', count(Permission::cases())));
});
