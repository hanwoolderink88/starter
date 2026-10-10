<?php

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

function lastActivity(): Activity
{
    return Activity::query()->latest('id')->firstOrFail();
}

function twoFactorUser(): User
{
    $user = User::factory()->create();

    $user->forceFill([
        'two_factor_secret' => encrypt(app(TwoFactorAuthenticationProvider::class)->generateSecretKey()),
        'two_factor_recovery_codes' => encrypt(json_encode(['recovery-1', 'recovery-2'])),
        'two_factor_confirmed_at' => now(),
    ])->save();

    return $user;
}

test('a login is logged with where it came from', function () {
    $user = User::factory()->create();

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
        ->withHeader('User-Agent', 'Firefox')
        ->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    expect(lastActivity())
        ->event->toBe(ActivityEvent::LoggedIn)
        ->subject_id->toBe($user->id)
        ->causer_id->toBe($user->id)
        ->ip_address->toBe('203.0.113.7')
        ->user_agent->toBe('Firefox')
        ->changes->toBe(['remember' => false]);
});

test('a wrong password is logged on the account, without the password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);

    $activity = lastActivity();

    expect($activity)
        ->event->toBe(ActivityEvent::LoginFailed)
        ->subject_id->toBe($user->id)
        ->causer_id->toBeNull()
        ->causer_name->toBe('Guest')
        ->changes->toBe(['email' => $user->email])
        ->and(json_encode($activity->getAttributes()))->not->toContain('wrong-password');
});

test('a login for an unknown email address is logged without a subject', function () {
    $this->post(route('login.store'), ['email' => 'Nobody@Example.com', 'password' => 'whatever']);

    expect(lastActivity())
        ->event->toBe(ActivityEvent::LoginFailed)
        ->subject_type->toBeNull()
        ->subject_id->toBeNull()
        ->changes->toBe(['email' => 'nobody@example.com']);
});

test('being throttled is logged', function () {
    $user = User::factory()->create();

    foreach (range(1, 6) as $attempt) {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password']);
    }

    expect(lastActivity())
        ->event->toBe(ActivityEvent::LockedOut)
        ->subject_id->toBe($user->id)
        ->changes->toBe(['email' => $user->email]);
});

test('a logout is logged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    expect(lastActivity())
        ->event->toBe(ActivityEvent::LoggedOut)
        ->subject_id->toBe($user->id)
        ->causer_id->toBe($user->id);
});

test('a rejected two-factor code is logged on the account', function () {
    $user = twoFactorUser();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('two-factor.login.store'), ['code' => '000000']);

    $this->assertGuest();
    expect(lastActivity())
        ->event->toBe(ActivityEvent::TwoFactorFailed)
        ->subject_id->toBe($user->id)
        ->causer_id->toBeNull();
});

test('logging in with a recovery code logs the code being used, not the code itself', function () {
    $user = twoFactorUser();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->post(route('two-factor.login.store'), ['recovery_code' => 'recovery-1']);

    $this->assertAuthenticatedAs($user);

    $events = Activity::query()->forSubject($user)->reorder('id')->pluck('event')->all();

    expect($events)->toBe([ActivityEvent::RecoveryCodeUsed, ActivityEvent::LoggedIn])
        ->and(Activity::query()->pluck('changes')->toJson())->not->toContain('recovery-1');
});

test('turning two-factor authentication on and off is logged', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->post(route('two-factor.enable'));

    expect(lastActivity())
        ->event->toBe(ActivityEvent::TwoFactorEnabled)
        ->subject_id->toBe($user->id)
        ->causer_id->toBe($user->id);

    $this->delete(route('two-factor.disable'));

    expect(lastActivity()->event)->toBe(ActivityEvent::TwoFactorDisabled);
});

test('resetting a forgotten password is logged with the user as actor', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors();

        return true;
    });

    expect(lastActivity())
        ->event->toBe(ActivityEvent::PasswordReset)
        ->subject_id->toBe($user->id)
        ->causer_id->toBe($user->id)
        ->changes->toBe([]);
});

test('changing the password is logged without the password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    expect(lastActivity())
        ->event->toBe(ActivityEvent::PasswordChanged)
        ->causer_id->toBe($user->id)
        ->changes->toBe([]);
});

test('registering is logged as the new user creating their account, then logging in', function () {
    seedUserRolesAndPermissions();

    $this->post(route('register.store'), [
        'name' => 'New Person',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'new@example.com')->sole();
    [$created, $loggedIn] = Activity::query()->forSubject($user)->reorder('id')->get()->all();

    expect($created)
        ->event->toBe(ActivityEvent::Created)
        ->causer_id->toBe($user->id)
        ->changes->toBe([
            'name' => ['old' => null, 'new' => 'New Person'],
            'email' => ['old' => null, 'new' => 'new@example.com'],
        ])
        ->and($loggedIn->event)->toBe(ActivityEvent::LoggedIn);
});
