<?php

use App\Features\Roles\Enums\Permission;
use App\Features\UserManagement\Events\UserChanged;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('admins can impersonate another user', function () {
    $admin = createAdmin();
    $target = createRegularUser();

    $this->actingAs($admin)
        ->post(route('users.impersonate', $target))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($target);
    expect(session(ChangeContext::IMPERSONATION_SESSION_KEY))->toBe([
        'impersonator_id' => $admin->id,
        'impersonated_id' => $target->id,
    ]);
});

test('starting is logged with the admin as actor and not as a login of the target', function () {
    $admin = createAdmin();
    $target = createRegularUser();

    $this->actingAs($admin)->post(route('users.impersonate', $target));

    expect(Activity::query()->forSubject($target)->sole())
        ->event->toBe(ActivityEvent::ImpersonationStarted)
        ->causer_id->toBe($admin->id)
        ->impersonator_id->toBeNull();
});

test('admins cannot impersonate themselves', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('users.impersonate', $admin))->assertForbidden();
});

test('nobody can impersonate a user who may impersonate', function () {
    $admin = createAdmin();
    $otherAdmin = createAdmin();
    $impersonator = createRegularUser();
    $impersonator->givePermissionTo(Permission::ImpersonateUsers);

    $this->actingAs($admin)->post(route('users.impersonate', $otherAdmin))->assertForbidden();
    $this->actingAs($admin)->post(route('users.impersonate', $impersonator))->assertForbidden();

    $this->assertAuthenticatedAs($admin);
    expect(Activity::query()->where('event', ActivityEvent::ImpersonationStarted)->exists())->toBeFalse();
});

test('users without the impersonate permission cannot impersonate', function () {
    $user = createRegularUser();
    $target = createRegularUser();

    $this->actingAs($user)->post(route('users.impersonate', $target))->assertForbidden();

    $this->assertAuthenticatedAs($user);
});

test('guests cannot impersonate', function () {
    $target = User::factory()->create();

    $this->post(route('users.impersonate', $target))->assertRedirect(route('login'));
});

test('the user list offers only the users the viewer may impersonate', function () {
    $admin = createAdmin();
    $otherAdmin = createAdmin();
    $target = createRegularUser();

    $this->actingAs($admin)->get(route('users.index'))
        ->assertInertia(fn (Assert $page) => $page->where('impersonatableIds', [$target->id]))
        ->assertInertia(fn (Assert $page) => $page->where('impersonatableIds', fn ($ids) => ! collect($ids)->contains($otherAdmin->id)));
});

test('the shared props name the impersonator', function () {
    $admin = createAdmin();
    $target = createRegularUser();

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.impersonatorName', null));

    $this->post(route('users.impersonate', $target));

    $this->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.id', $target->id)
            ->where('auth.impersonatorName', $admin->name));
});

test('a change made while impersonating names the impersonator in the signal and the log', function () {
    $admin = createAdmin();
    $target = createRegularUser();
    $target->givePermissionTo(Permission::ViewUsers, Permission::UpdateUsers);
    $colleague = createRegularUser();

    $this->actingAs($admin)->post(route('users.impersonate', $target));

    Event::fake([UserChanged::class]);

    $this->put(route('users.update', $colleague), ['version' => $colleague->fresh()->version,
        'name' => 'Renamed Colleague',
        'email' => $colleague->email,
    ])->assertSessionHasNoErrors();

    Event::assertDispatched(
        UserChanged::class,
        fn (UserChanged $event) => $event->payload->id === $colleague->id
            && $event->payload->actorId === $target->id
            && $event->payload->impersonatorName === $admin->name,
    );

    expect(Activity::query()->forSubject($colleague)->sole())
        ->event->toBe(ActivityEvent::Updated)
        ->causer_id->toBe($target->id)
        ->impersonator_id->toBe($admin->id)
        ->impersonator_name->toBe($admin->name);
});

test('stopping returns the impersonator to their own account and logs it', function () {
    $admin = createAdmin();
    $target = createRegularUser();

    $this->actingAs($admin)->post(route('users.impersonate', $target));

    $this->post(route('impersonation.stop'))->assertRedirect(route('users.show', $target));

    $this->assertAuthenticatedAs($admin);
    expect(session()->has(ChangeContext::IMPERSONATION_SESSION_KEY))->toBeFalse()
        ->and(Activity::query()->forSubject($target)->first())
        ->event->toBe(ActivityEvent::ImpersonationStopped)
        ->causer_id->toBe($admin->id)
        ->impersonator_id->toBeNull();
});

test('only an impersonating session can stop impersonating', function () {
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('impersonation.stop'))->assertForbidden();

    $this->assertAuthenticatedAs($admin);
});

test('guests cannot stop impersonating', function () {
    $this->post(route('impersonation.stop'))->assertRedirect(route('login'));
});

test('a stale impersonation marker names no impersonator', function () {
    $admin = createAdmin();
    $target = createRegularUser();
    $someoneElse = createRegularUser();

    $this->actingAs($someoneElse)
        ->withSession([ChangeContext::IMPERSONATION_SESSION_KEY => [
            'impersonator_id' => $admin->id,
            'impersonated_id' => $target->id,
        ]])
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.impersonatorName', null));

    $this->post(route('impersonation.stop'))->assertForbidden();

    $this->assertAuthenticatedAs($someoneElse);
});

test('you cannot impersonate someone else while impersonating', function () {
    $admin = createAdmin();
    $target = createRegularUser();
    $third = createRegularUser();

    $this->actingAs($admin)->post(route('users.impersonate', $target));

    // Granted while the impersonation lasts; it must still not nest. The
    // guards are forgotten so the next request loads the user afresh, as a
    // real request would.
    $target->givePermissionTo(Permission::ViewUsers, Permission::ImpersonateUsers);
    $this->app['auth']->forgetGuards();

    $this->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('impersonatableIds', []));

    $this->post(route('users.impersonate', $third))->assertForbidden();

    $this->assertAuthenticatedAs($target);
});

test('impersonating renews the session and drops the password confirmation', function () {
    $admin = createAdmin();
    $target = createRegularUser();

    $this->actingAs($admin)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('dashboard'));

    $sessionId = session()->getId();
    $csrfToken = session()->token();

    $this->post(route('users.impersonate', $target));

    expect(session()->getId())->not->toBe($sessionId)
        ->and(session()->token())->not->toBe($csrfToken)
        ->and(session()->has('auth.password_confirmed_at'))->toBeFalse();

    $sessionId = session()->getId();

    $this->post(route('impersonation.stop'));

    expect(session()->getId())->not->toBe($sessionId);
});

test('impersonating keeps the remember tokens of both users', function () {
    $admin = createAdmin();
    $target = createRegularUser();
    $admin->forceFill(['remember_token' => 'admin-token'])->save();
    $target->forceFill(['remember_token' => 'target-token'])->save();

    $this->actingAs($admin)->post(route('users.impersonate', $target));
    $this->post(route('impersonation.stop'));

    expect($admin->fresh()->remember_token)->toBe('admin-token')
        ->and($target->fresh()->remember_token)->toBe('target-token');
});

test('the session ends when the impersonator is deleted meanwhile', function () {
    $admin = createAdmin();
    $target = createRegularUser();
    $target->forceFill(['remember_token' => 'target-token'])->save();

    $this->actingAs($admin)->post(route('users.impersonate', $target));

    $admin->delete();

    $this->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
    expect($target->fresh()->remember_token)->toBe('target-token');
});

test('logging out while impersonating logs the stop and logs the impersonator out', function () {
    $admin = createAdmin();
    $target = createRegularUser();
    $target->forceFill(['remember_token' => 'target-token'])->save();

    $this->actingAs($admin)->post(route('users.impersonate', $target));

    $this->post(route('logout'))->assertRedirect('/');

    $this->assertGuest();
    expect(Activity::query()->forSubject($target)->first())
        ->event->toBe(ActivityEvent::ImpersonationStopped)
        ->causer_id->toBe($admin->id)
        ->and(Activity::query()->forSubject($admin)->first())
        ->event->toBe(ActivityEvent::LoggedOut)
        ->and($target->fresh()->remember_token)->toBe('target-token');
});

test('the account settings of the impersonated user cannot be changed', function (string $method, string $route) {
    $admin = createAdmin();
    $target = createRegularUser();

    $this->actingAs($admin)->post(route('users.impersonate', $target));

    $this->{$method}(route($route), [
        'name' => 'Changed',
        'email' => 'changed@example.com',
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertForbidden();

    expect($target->fresh())
        ->name->not->toBe('Changed')
        ->and(User::query()->whereKey($target->id)->exists())->toBeTrue();
})->with([
    'profile' => ['patch', 'profile.update'],
    'language' => ['patch', 'locale.update'],
    'sessions' => ['get', 'sessions.index'],
    'log out other sessions' => ['delete', 'sessions.destroy'],
    'delete account' => ['delete', 'profile.destroy'],
    'password' => ['put', 'user-password.update'],
    'two-factor settings' => ['get', 'two-factor.show'],
    'enable two-factor' => ['post', 'two-factor.enable'],
    'disable two-factor' => ['delete', 'two-factor.disable'],
    'recovery codes' => ['get', 'two-factor.recovery-codes'],
]);
