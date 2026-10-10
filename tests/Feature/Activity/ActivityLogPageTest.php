<?php

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('users with the view activity permission can read the log', function () {
    $viewer = userWithPermissions(Permission::ViewActivity);
    Activity::factory()->causedBy($viewer)->create();

    $this->actingAs($viewer)->get(route('activity.index', ['source' => 'web']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('activity/index')
            ->has('entries.data', 1)
            ->where('causerOptions', [['id' => $viewer->id, 'name' => $viewer->name]]));
});

test('users without the permission cannot read the log', function () {
    $this->actingAs(createRegularUser())->get(route('activity.index'))->assertForbidden();
});

test('the log filters on event, source, causer, subject and period', function () {
    $admin = createAdmin();
    $jane = User::factory()->create();
    $subject = User::factory()->create();

    $match = Activity::factory()->about($subject)->causedBy($jane)->create([
        'event' => ActivityEvent::LoggedIn,
        'source' => ActivitySource::Web,
        'created_at' => '2026-03-15 12:00:00',
    ]);
    Activity::factory()->about($subject)->causedBy($jane)->create(['event' => ActivityEvent::LoggedOut, 'created_at' => '2026-03-15 12:00:00']);
    Activity::factory()->about($subject)->causedBy($admin)->create(['event' => ActivityEvent::LoggedIn, 'created_at' => '2026-03-15 12:00:00']);
    Activity::factory()->about($subject)->causedBy($jane)->create(['event' => ActivityEvent::LoggedIn, 'created_at' => '2026-03-17 12:00:00']);
    Activity::factory()->about($subject)->causedBy($jane)->create(['event' => ActivityEvent::LoggedIn, 'source' => ActivitySource::Mcp, 'created_at' => '2026-03-15 12:00:00']);

    $this->actingAs($admin)->get(route('activity.index', [
        'event' => 'logged_in',
        'source' => 'web',
        'causer_id' => $jane->id,
        'subject_type' => 'user',
        'subject_id' => $subject->id,
        'from' => '2026-03-15',
        'to' => '2026-03-16',
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('entries.data', 1)
        ->where('entries.data.0.activity.id', $match->id)
        ->where('entries.data.0.subjectName', $subject->name));
});

test('filtering on a causer includes what they did while impersonating someone', function () {
    $admin = createAdmin();
    $piet = User::factory()->create();

    $entry = Activity::factory()->create(['impersonator_id' => $piet->id, 'impersonator_name' => $piet->name]);
    Activity::factory()->create();

    $this->actingAs($admin)->get(route('activity.index', ['causer_id' => $piet->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.activity.id', $entry->id));
});

test('unknown filter values are ignored', function () {
    $total = Activity::query()->count() + 2;
    Activity::factory()->count(2)->create();

    $this->actingAs(createAdmin())->get(route('activity.index', [
        'event' => 'exploded',
        'subject_type' => 'App\\Models\\User',
        'causer_id' => 'abc',
        'from' => '2026-02-31',
    ]))->assertInertia(fn (Assert $page) => $page
        ->has('entries.data', $total)
        ->where('filters.event', null)
        ->where('filters.subjectType', null)
        ->where('filters.causerId', null)
        ->where('filters.from', null));
});

test('a deleted subject is named by its snapshot', function () {
    $deleted = User::factory()->create(['name' => 'Gone Person']);
    Activity::factory()->about($deleted)->create([
        'event' => ActivityEvent::Deleted,
        'changes' => ['snapshot' => ['name' => 'Gone Person']],
    ]);
    $deleted->delete();

    $this->actingAs(createAdmin())->get(route('activity.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.data.0.subjectName', 'Gone Person')
            ->where('entries.data.0.subjectExists', false));
});

test('the log has no way to change or remove an entry', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn ($route) => str_starts_with($route->uri(), 'activity'));

    expect($routes->flatMap(fn ($route) => $route->methods())->unique()->values()->all())
        ->toEqualCanonicalizing(['GET', 'HEAD']);
});

test('the user page shows the user\'s timeline only to who may read the log', function () {
    $target = createRegularUser();
    Activity::factory()->about($target)->create();

    $viewer = createRegularUser();
    $viewer->givePermissionTo(Permission::ViewUsers);

    $this->actingAs($viewer)->get(route('users.show', $target))
        ->assertInertia(fn (Assert $page) => $page->where('activity', null));

    $this->actingAs(createAdmin())->get(route('users.show', $target))
        ->assertInertia(fn (Assert $page) => $page->has('activity', 1));
});

test('an inactive role is still named as the subject', function () {
    $role = Role::factory()->inactive()->create(['name' => 'Dormant']);
    Activity::factory()->about($role)->create();

    $this->actingAs(createAdmin())->get(route('activity.index', ['subject_type' => 'role', 'subject_id' => $role->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('entries.data.0.subjectName', 'Dormant')
            ->where('entries.data.0.subjectExists', true));
});
