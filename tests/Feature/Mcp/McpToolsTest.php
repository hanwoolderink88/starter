<?php

use App\Features\Activity\Mcp\Tools\SearchActivityTool;
use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Mcp\Tools\ListRolesTool;
use App\Features\UserManagement\Mcp\Tools\DeleteUserTool;
use App\Features\UserManagement\Mcp\Tools\ResendInvitationTool;
use App\Features\UserManagement\Mcp\Tools\RestoreUserTool;
use App\Features\UserManagement\Mcp\Tools\UpdateUserTool;
use App\Features\UserManagement\Notifications\InvitationNotification;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Activity\Models\Activity;
use App\Mcp\Servers\AppServer;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Transport\FakeTransporter;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

/**
 * @return list<class-string>
 */
function appServerTools(): array
{
    $server = new AppServer(new FakeTransporter);
    (new ReflectionMethod($server, 'boot'))->invoke($server);

    return (new ReflectionProperty($server, 'tools'))->getValue($server);
}

function hasAttribute(string $class, string $attribute): bool
{
    return (new ReflectionClass($class))->getAttributes($attribute) !== [];
}

test('tools that change nothing are read-only, and deleting is marked destructive', function () {
    $readOnly = array_values(array_filter(appServerTools(), fn (string $tool) => hasAttribute($tool, IsReadOnly::class)));
    $destructive = array_values(array_filter(appServerTools(), fn (string $tool) => hasAttribute($tool, IsDestructive::class)));

    expect(array_map(class_basename(...), $readOnly))->toEqualCanonicalizing(['SearchUsersTool', 'ShowUserTool', 'ListRolesTool', 'SearchActivityTool'])
        ->and(array_map(class_basename(...), $destructive))->toBe(['DeleteUserTool']);
});

test('the MCP endpoint is rate limited per account', function () {
    $route = collect(Route::getRoutes()->getRoutes())->first(fn ($route) => $route->uri() === 'mcp' && in_array('POST', $route->methods()));

    expect($route->gatherMiddleware())->toContain('throttle:mcp');
});

test('an agent updates a user with the same rules, logged as mcp', function () {
    $admin = createAdmin();
    $user = createRegularUser();

    AppServer::actingAs($admin)->tool(UpdateUserTool::class, [
        'user_id' => $user->id,
        'version' => $user->version,
        'name' => 'Renamed by Claude',
        'email' => $user->email,
        'roles' => ['User'],
    ])->assertOk()->assertSee('Renamed by Claude');

    expect(Activity::query()->forSubject($user)->sole())
        ->event->toBe(ActivityEvent::Updated)
        ->source->toBe(ActivitySource::Mcp)
        ->causer_id->toBe($admin->id);
});

test('an update based on an old version tells the agent the current one', function () {
    $user = createRegularUser();
    $stale = $user->version;
    $user->bumpVersion();

    AppServer::actingAs(createAdmin())->tool(UpdateUserTool::class, [
        'user_id' => $user->id,
        'version' => $stale,
        'name' => 'Too late',
        'email' => $user->email,
    ])->assertHasErrors(['version '.($stale + 1)]);

    expect($user->fresh()->name)->not->toBe('Too late');
});

test('giving roles over MCP needs the manage roles permission', function () {
    $editor = userWithPermissions(Permission::ViewUsers, Permission::UpdateUsers);
    $user = createRegularUser();

    AppServer::actingAs($editor)->tool(UpdateUserTool::class, [
        'user_id' => $user->id,
        'version' => $user->version,
        'name' => $user->name,
        'email' => $user->email,
        'roles' => ['Administrator'],
    ])->assertHasErrors();

    expect($user->fresh()->hasRole(administratorRole()))->toBeFalse();
});

test('an agent cannot take away the last administrator', function () {
    $admin = createAdmin();

    AppServer::actingAs($admin)->tool(UpdateUserTool::class, [
        'user_id' => $admin->id,
        'version' => $admin->version,
        'name' => $admin->name,
        'email' => $admin->email,
        'roles' => ['User'],
    ])->assertHasErrors([__('roles.last_administrator')]);
});

test('an agent deletes and restores a user', function () {
    $admin = createAdmin();
    $user = createRegularUser();

    AppServer::actingAs($admin)->tool(DeleteUserTool::class, ['user_id' => $user->id])->assertOk();
    $this->assertSoftDeleted($user);

    AppServer::actingAs($admin)->tool(RestoreUserTool::class, ['user_id' => $user->id])->assertOk();

    expect($user->fresh()->trashed())->toBeFalse()
        ->and(Activity::query()->forSubject($user)->where('source', ActivitySource::Mcp)->pluck('event')->all())
        ->toEqualCanonicalizing([ActivityEvent::Deleted, ActivityEvent::Restored]);
});

test('an agent resends an invitation, only to someone still invited', function () {
    Notification::fake();
    $admin = createAdmin();
    $invited = User::factory()->invited()->create();

    AppServer::actingAs($admin)->tool(ResendInvitationTool::class, ['user_id' => $invited->id])->assertOk();
    Notification::assertSentTo($invited, InvitationNotification::class);

    AppServer::actingAs($admin)->tool(ResendInvitationTool::class, ['user_id' => createRegularUser()->id])->assertHasErrors();
});

test('an agent lists the roles, with permissions only for who may manage them', function () {
    AppServer::actingAs(createAdmin())->tool(ListRolesTool::class)
        ->assertOk()
        ->assertSee('Administrator')
        ->assertSee('manage roles');

    AppServer::actingAs(userWithPermissions(Permission::CreateUsers))->tool(ListRolesTool::class)
        ->assertOk()
        ->assertSee('Administrator')
        ->assertDontSee('manage roles');

    AppServer::actingAs(createRegularUser())->tool(ListRolesTool::class)
        ->assertHasErrors(['Tool [list_roles] not found.']);
});

test('an agent searches the activity log, without IP addresses or browsers', function () {
    $admin = createAdmin();
    $jane = createRegularUser();
    Activity::factory()->about($jane)->causedBy($admin)->create([
        'event' => ActivityEvent::Updated,
        'ip_address' => '203.0.113.7',
        'user_agent' => 'Firefox/123',
    ]);

    AppServer::actingAs($admin)->tool(SearchActivityTool::class, ['subject_type' => 'user', 'subject_id' => $jane->id])
        ->assertOk()
        ->assertSee($jane->name)
        ->assertSee('updated')
        ->assertDontSee('203.0.113.7')
        ->assertDontSee('Firefox/123');

    AppServer::actingAs(createRegularUser())->tool(SearchActivityTool::class)
        ->assertHasErrors(['Tool [search_activity] not found.']);
});
