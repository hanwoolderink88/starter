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

test('guests cannot access users index', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});

test('regular users cannot access users index', function () {
    $this->actingAs(createRegularUser());

    $this->get(route('users.index'))->assertForbidden();
});

test('admins can view users index', function () {
    $this->actingAs(createAdmin());

    $this->get(route('users.index'))->assertOk();
});

test('admins can view create user page', function () {
    $this->actingAs(createAdmin());

    $this->get(route('users.create'))->assertOk();
});

test('admins can create a user', function () {
    $this->actingAs(createAdmin());

    $this->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'role' => Role::User->value,
    ])->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'password' => null,
    ]);
});

// TODO: Uncomment when Task 4 creates invitation.accept route
// test('invitation notification is sent when creating a user', function () {
//     Notification::fake();
//
//     $this->actingAs(createAdmin());
//
//     $this->post(route('users.store'), [
//         'name' => 'New User',
//         'email' => 'newuser@example.com',
//         'role' => Role::User->value,
//     ])->assertRedirect(route('users.index'));
//
//     $user = User::where('email', 'newuser@example.com')->first();
//
//     Notification::assertSentTo($user, InvitationNotification::class);
// });

test('store validates required fields', function () {
    $this->actingAs(createAdmin());

    $this->post(route('users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'role']);
});

test('store validates unique email', function () {
    $this->actingAs(createAdmin());
    $existing = createRegularUser();

    $this->post(route('users.store'), [
        'name' => 'Duplicate',
        'email' => $existing->email,
        'role' => Role::User->value,
    ])->assertSessionHasErrors(['email']);
});

test('admins can view edit user page', function () {
    $this->actingAs(createAdmin());
    $user = createRegularUser();

    $this->get(route('users.edit', $user))->assertOk();
});

test('admins can update a user', function () {
    $this->actingAs(createAdmin());
    $user = createRegularUser();

    $this->put(route('users.update', $user), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => Role::SuperAdmin->value,
    ])->assertRedirect(route('users.index'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('admins can delete a user', function () {
    $this->actingAs(createAdmin());
    $user = createRegularUser();

    $this->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('admins cannot delete themselves', function () {
    $admin = createAdmin();
    $this->actingAs($admin);

    $this->delete(route('users.destroy', $admin))->assertForbidden();
});

test('regular users cannot create users', function () {
    $this->actingAs(createRegularUser());

    $this->post(route('users.store'), [
        'name' => 'Test',
        'email' => 'test@example.com',
        'role' => Role::User->value,
    ])->assertForbidden();
});

test('regular users cannot update users', function () {
    $this->actingAs(createRegularUser());
    $target = createRegularUser();

    $this->put(route('users.update', $target), [
        'name' => 'Hacked',
        'email' => 'hacked@example.com',
        'role' => Role::User->value,
    ])->assertForbidden();
});

test('regular users cannot delete users', function () {
    $this->actingAs(createRegularUser());
    $target = createRegularUser();

    $this->delete(route('users.destroy', $target))->assertForbidden();
});
