<?php

use App\Features\UserManagement\Mcp\Servers\UserManagementServer;
use App\Features\UserManagement\Mcp\Tools\CreateUserTool;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    seedUserRolesAndPermissions();
    Notification::fake();
});

test('creating a user is logged with its initial values and roles', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role_ids' => [userRole()->id],
    ]);

    $user = User::query()->where('email', 'jane@example.com')->sole();
    [$created, $roles] = Activity::query()->forSubject($user)->reorder('id')->get()->all();

    expect($created)
        ->event->toBe(ActivityEvent::Created)
        ->causer_id->toBe($admin->id)
        ->source->toBe(ActivitySource::Web)
        ->changes->toBe([
            'name' => ['old' => null, 'new' => 'Jane Doe'],
            'email' => ['old' => null, 'new' => 'jane@example.com'],
        ])
        ->and($roles)
        ->event->toBe(ActivityEvent::RolesChanged)
        ->changes->toBe(['roles' => ['old' => [], 'new' => ['User']]]);
});

test('creating a user through MCP is logged with source mcp', function () {
    $admin = createAdmin();

    UserManagementServer::actingAs($admin)
        ->tool(CreateUserTool::class, ['name' => 'Via Claude', 'email' => 'claude@example.com', 'roles' => ['User']])
        ->assertOk();

    $user = User::query()->where('email', 'claude@example.com')->sole();

    expect($user->getRoleNames()->all())->toBe(['User'])
        ->and(Activity::query()->forSubject($user)->reorder('id')->first())
        ->event->toBe(ActivityEvent::Created)
        ->causer_id->toBe($admin->id)
        ->source->toBe(ActivitySource::Mcp);
});

test('updating a user logs only what changed', function () {
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)->put(route('users.update', $user), ['version' => $user->fresh()->version,
        'name' => 'New Name',
        'email' => $user->email,
        'role_ids' => [administratorRole()->id],
    ]);

    [$updated, $roles] = Activity::query()->forSubject($user)->reorder('id')->get()->all();

    expect($updated)
        ->event->toBe(ActivityEvent::Updated)
        ->changes->toBe(['name' => ['old' => $user->name, 'new' => 'New Name']])
        ->and($roles)
        ->event->toBe(ActivityEvent::RolesChanged)
        ->changes->toBe(['roles' => ['old' => ['User'], 'new' => ['Administrator']]]);
});

test('an update that changes nothing writes no entry', function () {
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)->put(route('users.update', $user), ['version' => $user->fresh()->version,
        'name' => $user->name,
        'email' => $user->email,
        'role_ids' => [userRole()->id],
    ]);

    expect(Activity::query()->forSubject($user)->exists())->toBeFalse();
});

test('deleting a user keeps a snapshot of who they were', function () {
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)->delete(route('users.destroy', $user));

    expect(Activity::query()->where('event', ActivityEvent::Deleted)->sole())
        ->subject_id->toBe($user->id)
        ->causer_id->toBe($admin->id)
        ->changes->toBe(['snapshot' => ['name' => $user->name, 'email' => $user->email, 'roles' => ['User']]]);
});

test('a deleted user keeps their entries', function () {
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)->delete(route('users.destroy', $user));
    $admin->delete();

    expect(Activity::query()->where('causer_id', $admin->id)->sole())
        ->causer_name->toBe($admin->name);
});

test('resending an invitation is logged', function () {
    $admin = createAdmin();
    $invited = User::factory()->create(['password' => null]);

    $this->actingAs($admin)->post(route('users.resend-invitation', $invited));

    expect(Activity::query()->forSubject($invited)->sole())
        ->event->toBe(ActivityEvent::InvitationResent)
        ->causer_id->toBe($admin->id);
});

test('accepting an invitation is logged with the invited user as actor', function () {
    $invited = User::factory()->create(['password' => null]);

    $this->post(route('invitation.store', $invited), [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    [$accepted, $loggedIn] = Activity::query()->forSubject($invited)->reorder('id')->get()->all();

    expect($accepted)
        ->event->toBe(ActivityEvent::InvitationAccepted)
        ->causer_id->toBe($invited->id)
        ->and($loggedIn->event)->toBe(ActivityEvent::LoggedIn);
});
