<?php

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('saving a user based on an old version is refused and names who saved first', function () {
    $jane = createAdmin();
    $piet = createAdmin();
    $user = createRegularUser();
    $opened = $user->version;

    $this->actingAs($jane)->put(route('users.update', $user), [
        'version' => $opened,
        'name' => 'Saved by Jane',
        'email' => $user->email,
    ])->assertSessionHasNoErrors();

    $this->actingAs($piet)->put(route('users.update', $user), [
        'version' => $opened,
        'name' => 'Saved by Piet',
        'email' => $user->email,
    ])->assertSessionHasErrors('version');

    expect(session('errors')->first('version'))
        ->toContain($jane->name)
        ->toContain('version '.($opened + 1))
        ->and($user->fresh())
        ->name->toBe('Saved by Jane')
        ->version->toBe($opened + 1);
});

test('two saves within the same second still conflict', function () {
    $user = createRegularUser();
    $admin = createAdmin();
    $this->freezeTime();

    $payload = ['version' => $user->version, 'name' => 'First', 'email' => $user->email];

    $this->actingAs($admin)->put(route('users.update', $user), $payload)->assertSessionHasNoErrors();
    $this->actingAs($admin)->put(route('users.update', $user), [...$payload, 'name' => 'Second'])->assertSessionHasErrors('version');

    expect($user->fresh()->name)->toBe('First');
});

test('a refused save also leaves the roles alone', function () {
    $admin = createAdmin();
    $user = createRegularUser();
    $stale = $user->version;
    $user->bumpVersion();

    $this->actingAs($admin)->put(route('users.update', $user), [
        'version' => $stale,
        'name' => $user->name,
        'email' => $user->email,
        'role_ids' => [administratorRole()->id],
    ])->assertSessionHasErrors('version');

    expect($user->fresh()->hasRole(administratorRole()))->toBeFalse();
});

test('a user editing their own profile makes an older admin form stale', function () {
    $admin = createAdmin();
    $user = createRegularUser();
    $opened = $user->version;

    $this->actingAs($user)->patch(route('profile.update'), ['name' => 'My own name', 'email' => $user->email])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)->put(route('users.update', $user), [
        'version' => $opened,
        'name' => 'Admin name',
        'email' => $user->email,
    ])->assertSessionHasErrors('version');

    expect($user->fresh()->name)->toBe('My own name');
});

test('saving a role based on an old version is refused', function () {
    $role = Role::factory()->withPermissions([Permission::ViewUsers])->create();
    $admin = createAdmin();
    $opened = $role->version;

    $this->actingAs($admin)->post(route('roles.toggle-active', $role));

    $this->actingAs($admin)->put(route('roles.update', $role), [
        'version' => $opened,
        'name' => 'Overwritten',
        'permissions' => [],
    ])->assertSessionHasErrors('version');

    expect($role->fresh())
        ->name->not->toBe('Overwritten')
        ->and($role->fresh()->permissions->pluck('name')->all())->toBe(['view users']);
});

test('the edit forms carry the version', function () {
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)->get(route('users.edit', $user))
        ->assertInertia(fn (Assert $page) => $page->where('user.version', $user->version));

    $this->actingAs($admin)->get(route('roles.edit', userRole()))
        ->assertInertia(fn (Assert $page) => $page->where('role.version', userRole()->version));
});
