<?php

use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Enums\UserStatus;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('index can search users by name or email', function () {
    $admin = createAdmin();
    User::factory()->create(['name' => 'Alice Searcher', 'email' => 'alice@example.com']);
    User::factory()->create(['name' => 'Bob Other', 'email' => 'bob@example.com']);

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'alice']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('user-management/index')
            ->has('users.data', 1)
            ->where('users.data.0.email', 'alice@example.com'));
});

test('index can filter users by role', function () {
    $admin = createAdmin();
    createRegularUser();

    $this->actingAs($admin)
        ->get(route('users.index', ['role' => Role::SuperAdmin->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $admin->id));
});

test('index can filter users by status', function () {
    $admin = createAdmin();
    $invited = User::factory()->invited()->create();

    $this->actingAs($admin)
        ->get(route('users.index', ['status' => UserStatus::Invited->value]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $invited->id));
});

test('index sorts users by name descending', function () {
    $admin = createAdmin();
    User::factory()->create(['name' => 'Sortable Aaron', 'email' => 'a-sort@example.com']);
    User::factory()->create(['name' => 'Sortable Zoe', 'email' => 'z-sort@example.com']);

    $this->actingAs($admin)
        ->get(route('users.index', [
            'search' => 'Sortable',
            'sort' => 'name',
            'direction' => 'desc',
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 2)
            ->where('users.data.0.name', 'Sortable Zoe')
            ->where('users.data.1.name', 'Sortable Aaron'));
});

test('index paginates at ten per page', function () {
    $admin = createAdmin();
    User::factory()->count(15)->create();

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 10)
            ->where('users.meta.per_page', 10)
            ->where('users.meta.total', 16)
            ->where('users.meta.last_page', 2));
});

test('admins can view the user show page', function () {
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)
        ->get(route('users.show', $user))
        ->assertInertia(fn (Assert $page) => $page
            ->component('user-management/show')
            ->where('user.id', $user->id)
            ->where('canUpdate', true));
});

test('regular users cannot view the user show page', function () {
    $this->actingAs(createRegularUser());

    $this->get(route('users.show', createRegularUser()))->assertForbidden();
});

test('guests are redirected from the user show page', function () {
    $this->get(route('users.show', createRegularUser()))
        ->assertRedirect(route('login'));
});
