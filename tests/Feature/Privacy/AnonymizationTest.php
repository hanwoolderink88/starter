<?php

use App\Features\UserManagement\Actions\AnonymizeUserAction;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use App\Infrastructure\Activity\Services\ActivityPrivacy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('a user deleted longer ago than the term is anonymized, one within it is not', function () {
    $old = createRegularUser();
    $recent = createRegularUser();

    $this->travelTo(now()->subDays(31), fn () => $old->delete());
    $this->travelTo(now()->subDays(10), fn () => $recent->delete());

    $this->artisan('users:anonymize')->assertSuccessful();

    expect($old->fresh())
        ->name->toBe("Deleted user #{$old->id}")
        ->email->toBe("deleted-user-{$old->id}@anonymized.invalid")
        ->password->toBeNull()
        ->anonymized_at->not->toBeNull()
        ->and($old->fresh()->roles)->toBeEmpty()
        ->and($recent->fresh())->anonymized_at->toBeNull()
        ->name->toBe($recent->name);
});

test('anonymizing removes the user from every log entry but keeps the entries', function () {
    $admin = createAdmin();
    $jane = createRegularUser();
    $jane->forceFill(['name' => 'Jane Doe', 'email' => 'jane@example.com'])->save();

    // Jane acts, is impersonated, and is the subject of changes and failed logins.
    $this->actingAs($admin)->put(route('users.update', $jane), ['version' => $jane->fresh()->version, 'name' => 'Jane Smith', 'email' => 'jane.smith@example.com']);
    $this->post(route('users.impersonate', $jane));
    $this->post(route('impersonation.stop'));
    auth()->logout();
    $this->post(route('login.store'), ['email' => 'jane.smith@example.com', 'password' => 'wrong']);
    $this->actingAs($jane->fresh())->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);
    $this->actingAs($admin)->delete(route('users.destroy', $jane));

    $entries = Activity::query()->count();

    app(AnonymizeUserAction::class)->handle($jane->fresh(), ChangeContext::system());

    $log = Activity::query()->get()->toJson();

    expect($log)->not->toContain('Jane')
        ->not->toContain('jane@example.com')
        ->not->toContain('jane.smith@example.com')
        ->and(Activity::query()->where('causer_id', $jane->id)->whereNotNull('ip_address')->exists())->toBeFalse()
        ->and(Activity::query()->count())->toBe($entries + 1)
        ->and(Activity::query()->latest('id')->first())
        ->event->toBe(ActivityEvent::Anonymized)
        ->subject_id->toBe($jane->id);
});

test('a field change keeps its shape after anonymizing', function () {
    $admin = createAdmin();
    $jane = createRegularUser();

    $this->actingAs($admin)->put(route('users.update', $jane), ['version' => $jane->fresh()->version, 'name' => 'Renamed', 'email' => $jane->email]);
    $jane->delete();

    app(AnonymizeUserAction::class)->handle($jane->fresh(), ChangeContext::system());

    expect(Activity::query()->forSubject($jane)->where('event', ActivityEvent::Updated)->sole()->changes)
        ->toBe(['name' => ['old' => "Deleted user #{$jane->id}", 'new' => "Deleted user #{$jane->id}"]]);
});

test('an anonymized user cannot be restored', function () {
    $user = createRegularUser();
    $user->delete();
    app(AnonymizeUserAction::class)->handle($user->fresh(), ChangeContext::system());

    $this->actingAs(createAdmin())->post(route('users.restore', $user))->assertForbidden();
});

test('the log stays append-only outside privacy maintenance', function () {
    $activity = Activity::factory()->create();

    expect(fn () => DB::transaction(fn () => DB::table('activities')->where('id', $activity->id)->update(['causer_name' => 'x'])))
        ->toThrow(QueryException::class, 'append-only');

    // Maintenance does not leave its permission behind, also when it runs
    // inside a larger transaction.
    app(ActivityPrivacy::class)->clearNetworkDetailsBefore(now());

    expect(fn () => DB::transaction(fn () => DB::table('activities')->where('id', $activity->id)->delete()))
        ->toThrow(QueryException::class, 'append-only');
});
