<?php

use App\Features\Roles\Enums\Permission;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Passport\Passport;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('a deleted user can no longer log in and loses their sessions and tokens', function () {
    // Sessions are only kept, and so only removed, in the database driver.
    config(['session.driver' => 'database']);
    $user = createRegularUser();
    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $user->id, 'ip_address' => '203.0.113.7', 'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => time()]);
    $token = Passport::token()->newQuery()->forceCreate([
        'id' => 'token-1',
        'user_id' => $user->id,
        'client_id' => (string) Str::uuid(),
        'revoked' => false,
    ]);

    $this->actingAs(createAdmin())->delete(route('users.destroy', $user))->assertRedirect();
    auth()->logout();

    $this->assertSoftDeleted($user);
    expect(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(Passport::token()->newQuery()->whereKey($token->getKey())->exists())->toBeFalse();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    $this->assertGuest();
});

test('the address of a deleted user can be invited again', function () {
    Notification::fake();
    $deleted = createRegularUser();
    $deleted->delete();

    $this->actingAs(createAdmin())->post(route('users.store'), [
        'name' => 'Comeback',
        'email' => $deleted->email,
    ])->assertSessionHasNoErrors();

    expect(User::query()->where('email', $deleted->email)->count())->toBe(1);
});

test('deleted users are listed separately and can be restored, which is logged', function () {
    $admin = createAdmin();
    $user = createRegularUser();
    $user->delete();

    $this->actingAs($admin)->get(route('users.index', ['status' => 'deleted']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.id', $user->id)
            ->whereNot('users.data.0.anonymizes_on_display', null));

    $this->actingAs($admin)->post(route('users.restore', $user))->assertRedirect();

    expect($user->fresh()->trashed())->toBeFalse()
        ->and(Activity::query()->forSubject($user)->first()->event)->toBe(ActivityEvent::Restored);
});

test('restoring needs the delete users permission', function () {
    $user = createRegularUser();
    $user->delete();

    $this->actingAs(userWithPermissions(Permission::ViewUsers))
        ->post(route('users.restore', $user))
        ->assertForbidden();
});

test('deleting your own account is a logged soft delete too', function () {
    $user = createRegularUser();

    $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect();

    $this->assertSoftDeleted($user);
    expect(Activity::query()->forSubject($user)->where('event', ActivityEvent::Deleted)->sole())
        ->causer_id->toBe($user->id);
});
