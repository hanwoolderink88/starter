<?php

use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Mcp\Servers\UserManagementServer;
use App\Features\UserManagement\Mcp\Tools\CreateUserTool;
use App\Features\UserManagement\Mcp\Tools\SearchUsersTool;
use App\Features\UserManagement\Mcp\Tools\ShowUserTool;
use App\Features\UserManagement\Notifications\InvitationNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

// --- search_user ----------------------------------------------------------

test('super admin can search users', function () {
    $admin = createAdmin();
    User::factory()->create(['name' => 'Searchable Person', 'email' => 'searchable@example.com']);

    UserManagementServer::actingAs($admin)
        ->tool(SearchUsersTool::class, ['search' => 'searchable'])
        ->assertOk()
        ->assertSee('searchable@example.com');
});

test('search_user is hidden from users without view permission', function () {
    UserManagementServer::actingAs(createRegularUser())
        ->tool(SearchUsersTool::class, ['search' => 'anything'])
        ->assertHasErrors(['Tool [search_user] not found.']);
});

// --- user_detail -----------------------------------------------------------

test('super admin can view user detail', function () {
    $admin = createAdmin();
    $target = User::factory()->create(['email' => 'target@example.com']);

    UserManagementServer::actingAs($admin)
        ->tool(ShowUserTool::class, ['user_id' => $target->id])
        ->assertOk()
        ->assertSee('target@example.com');
});

test('user_detail returns an error for a missing user', function () {
    UserManagementServer::actingAs(createAdmin())
        ->tool(ShowUserTool::class, ['user_id' => 999999])
        ->assertHasErrors(['User not found.']);
});

test('user_detail is hidden from users without view permission', function () {
    $target = User::factory()->create();

    UserManagementServer::actingAs(createRegularUser())
        ->tool(ShowUserTool::class, ['user_id' => $target->id])
        ->assertHasErrors(['Tool [user_detail] not found.']);
});

// --- user_create -----------------------------------------------------------

test('super admin can create a user and an invitation is sent', function () {
    Notification::fake();
    $admin = createAdmin();

    UserManagementServer::actingAs($admin)
        ->tool(CreateUserTool::class, [
            'name' => 'Created Via Mcp',
            'email' => 'mcp-created@example.com',
            'role' => Role::User->value,
        ])
        ->assertOk()
        ->assertSee('mcp-created@example.com');

    $this->assertDatabaseHas('users', [
        'email' => 'mcp-created@example.com',
        'password' => null,
    ]);

    $created = User::where('email', 'mcp-created@example.com')->firstOrFail();
    Notification::assertSentTo($created, InvitationNotification::class);
});

test('user_create works when authenticated via the api guard', function () {
    // Mirrors the real MCP path: Passport's auth:api calls Auth::shouldUse('api'),
    // so role resolution must still target the web guard where roles are defined.
    Notification::fake();

    UserManagementServer::actingAs(createAdmin(), 'api')
        ->tool(CreateUserTool::class, [
            'name' => 'Api Guard User',
            'email' => 'api-guard@example.com',
            'role' => Role::User->value,
        ])
        ->assertOk()
        ->assertSee('api-guard@example.com');

    $this->assertDatabaseHas('users', ['email' => 'api-guard@example.com']);
});

test('user_create validates a unique email', function () {
    $admin = createAdmin();
    $existing = User::factory()->create(['email' => 'taken@example.com']);

    UserManagementServer::actingAs($admin)
        ->tool(CreateUserTool::class, [
            'name' => 'Duplicate',
            'email' => $existing->email,
            'role' => Role::User->value,
        ])
        ->assertHasErrors();
});

test('user_create is hidden from users without create permission and creates nothing', function () {
    UserManagementServer::actingAs(createRegularUser())
        ->tool(CreateUserTool::class, [
            'name' => 'Should Not Exist',
            'email' => 'blocked@example.com',
            'role' => Role::User->value,
        ])
        ->assertHasErrors(['Tool [user_create] not found.']);

    $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
});
