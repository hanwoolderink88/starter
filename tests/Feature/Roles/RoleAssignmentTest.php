<?php

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Features\UserManagement\Mcp\Servers\UserManagementServer;
use App\Features\UserManagement\Mcp\Tools\CreateUserTool;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedUserRolesAndPermissions();
    Notification::fake();
});

test('a user can have several roles and gets the permissions of all of them', function () {
    $viewer = Role::factory()->withPermissions([Permission::ViewUsers])->create();
    $auditor = Role::factory()->withPermissions([Permission::ViewActivity])->create();
    $user = createRegularUser();

    $this->actingAs(createAdmin())->put(route('users.update', $user), ['version' => $user->fresh()->version,
        'name' => $user->name,
        'email' => $user->email,
        'role_ids' => [$viewer->id, $auditor->id],
    ])->assertSessionHasNoErrors();

    $user = $user->fresh();

    expect($user->getRoleNames()->sort()->values()->all())->toBe(collect([$viewer->name, $auditor->name])->sort()->values()->all())
        ->and($user->can(Permission::ViewUsers->value))->toBeTrue()
        ->and($user->can(Permission::ViewActivity->value))->toBeTrue()
        ->and(Activity::query()->forSubject($user)->sole()->event)->toBe(ActivityEvent::RolesChanged);
});

test('giving roles needs the manage roles permission', function () {
    $editor = userWithPermissions(Permission::ViewUsers, Permission::UpdateUsers);
    $target = createRegularUser();

    $this->actingAs($editor)->put(route('users.update', $target), ['version' => $target->fresh()->version,
        'name' => $target->name,
        'email' => $target->email,
        'role_ids' => [administratorRole()->id],
    ])->assertForbidden();

    expect($target->fresh()->hasRole(administratorRole()))->toBeFalse();
});

test('without the manage roles permission a user can still be renamed', function () {
    $editor = userWithPermissions(Permission::ViewUsers, Permission::UpdateUsers);
    $target = createRegularUser();

    $this->actingAs($editor)->put(route('users.update', $target), ['version' => $target->fresh()->version,
        'name' => 'Renamed',
        'email' => $target->email,
        'role_ids' => [userRole()->id],
    ])->assertSessionHasNoErrors();

    $this->actingAs($editor)->put(route('users.update', $target), ['version' => $target->fresh()->version,
        'name' => 'Renamed again',
        'email' => $target->email,
    ])->assertSessionHasNoErrors();

    expect($target->fresh()->name)->toBe('Renamed again');
});

test('the edit form tells whether the viewer may change roles', function () {
    $target = createRegularUser();

    $this->actingAs(createAdmin())->get(route('users.edit', $target))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canAssignRoles', true)
            ->where('roleIds', [userRole()->id])
            ->has('roles', 2));

    $this->actingAs(userWithPermissions(Permission::ViewUsers, Permission::UpdateUsers))->get(route('users.edit', $target))
        ->assertInertia(fn (Assert $page) => $page->where('canAssignRoles', false));
});

test('an inactive role cannot be given', function () {
    $inactive = Role::factory()->inactive()->create();
    $target = createRegularUser();

    $this->actingAs(createAdmin())->put(route('users.update', $target), ['version' => $target->fresh()->version,
        'name' => $target->name,
        'email' => $target->email,
        'role_ids' => [$inactive->id],
    ])->assertSessionHasErrors('role_ids.0');
});

test('saving the form keeps an inactive assignment, which is taken away on its own', function () {
    $role = Role::factory()->create();
    $target = createRegularUser();
    $target->assignRole($role);
    $role->update(['is_active' => false]);

    $admin = createAdmin();

    $this->actingAs($admin)->put(route('users.update', $target), ['version' => $target->fresh()->version,
        'name' => $target->name,
        'email' => $target->email,
        'role_ids' => [],
    ])->assertSessionHasNoErrors();

    $allRoleIds = fn () => $target->roles()->withoutGlobalScope(Role::ACTIVE_SCOPE)->pluck('id')->sort()->values()->all();

    expect($allRoleIds())->toBe([$role->id]);

    $this->delete(route('users.roles.destroy', ['user' => $target, 'role' => $role->id]))->assertRedirect();

    expect($allRoleIds())->toBe([]);
});

test('the last administrator can neither lose the role nor be deleted', function () {
    $admin = createAdmin();
    $other = createAdmin();
    $other->delete();

    $this->actingAs($admin)->put(route('users.update', $admin), ['version' => $admin->fresh()->version,
        'name' => $admin->name,
        'email' => $admin->email,
        'role_ids' => [userRole()->id],
    ])->assertSessionHasErrors('role_ids');

    $this->actingAs($admin)->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertSessionHasErrors('password');

    expect($admin->fresh()->hasRole(administratorRole()))->toBeTrue();
});

test('an invited administrator does not count as another administrator', function () {
    $admin = createAdmin();
    $invited = User::factory()->invited()->create();
    $invited->assignRole(administratorRole());
    $manager = userWithPermissions(Permission::ViewUsers, Permission::DeleteUsers);

    $this->actingAs($manager)->delete(route('users.destroy', $admin))->assertSessionHasErrors('user');

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

test('an administrator can step down once there is another one', function () {
    $admin = createAdmin();
    createAdmin();

    $this->actingAs($admin)->put(route('users.update', $admin), ['version' => $admin->fresh()->version,
        'name' => $admin->name,
        'email' => $admin->email,
        'role_ids' => [userRole()->id],
    ])->assertSessionHasNoErrors();

    expect($admin->fresh()->hasRole(administratorRole()))->toBeFalse();
});

test('the user list filters on a role', function () {
    $admin = createAdmin();
    createRegularUser();

    $this->actingAs($admin)->get(route('users.index', ['role' => administratorRole()->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.roles', ['Administrator']));
});

test('creating a user with roles over MCP needs the manage roles permission', function () {
    $creator = userWithPermissions(Permission::CreateUsers);

    UserManagementServer::actingAs($creator)
        ->tool(CreateUserTool::class, ['name' => 'No Roles', 'email' => 'no-roles@example.com'])
        ->assertOk();

    UserManagementServer::actingAs($creator)
        ->tool(CreateUserTool::class, ['name' => 'Admin', 'email' => 'sneaky@example.com', 'roles' => ['Administrator']])
        ->assertHasErrors();

    expect(User::query()->where('email', 'sneaky@example.com')->exists())->toBeFalse();
});
