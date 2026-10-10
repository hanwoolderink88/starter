<?php

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['session.driver' => 'database']);
});

function storeSession(User $user, string $id, string $agent, int $minutesAgo = 0): void
{
    DB::table('sessions')->insert([
        'id' => $id,
        'user_id' => $user->id,
        'ip_address' => '203.0.113.7',
        'user_agent' => $agent,
        'payload' => '',
        'last_activity' => now()->subMinutes($minutesAgo)->getTimestamp(),
    ]);
}

test('a user sees their own sessions, without their ids', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    storeSession($user, 'phone-session', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1', 5);
    storeSession($other, 'someone-else', 'Firefox');

    $response = $this->actingAs($user)->get(route('sessions.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/sessions')
        ->where('available', true)
        ->has('sessions', 1)
        ->where('sessions.0.browser', 'Safari')
        ->where('sessions.0.platform', 'iOS')
        ->where('sessions.0.isDesktop', false)
        ->where('sessions.0.isCurrentDevice', false));

    expect($response->getContent())->not->toContain('phone-session')->not->toContain('someone-else');
});

test('logging out other sessions needs the password', function () {
    $user = User::factory()->create();
    storeSession($user, 'laptop', 'Firefox');

    $this->actingAs($user)->delete(route('sessions.destroy'), ['password' => 'wrong'])
        ->assertSessionHasErrors('password');

    expect(DB::table('sessions')->where('id', 'laptop')->exists())->toBeTrue();
});

test('logging out other sessions removes them, rehashes the password and is logged', function () {
    $user = User::factory()->create();
    $hash = $user->password;
    storeSession($user, 'laptop', 'Firefox');
    storeSession($user, 'phone', 'Safari');

    $this->actingAs($user)->delete(route('sessions.destroy'), ['password' => 'password'])
        ->assertRedirect(route('sessions.index'));

    $user->refresh();

    expect(DB::table('sessions')->where('user_id', $user->id)->whereIn('id', ['laptop', 'phone'])->exists())->toBeFalse()
        ->and($user->password)->not->toBe($hash)
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and(Activity::query()->forSubject($user)->where('event', ActivityEvent::OtherSessionsLoggedOut)->sole()->changes)
        ->toBe(['sessions' => 2]);
});

test('a session ends once the password changed elsewhere', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);
    $this->get(route('dashboard'))->assertOk();

    // Another device changes the password (or logs out the other sessions).
    User::query()->whereKey($user->id)->update(['password' => Hash::make('new-password')]);
    $this->app['auth']->forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});
