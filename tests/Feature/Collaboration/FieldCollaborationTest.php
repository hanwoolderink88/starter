<?php

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use App\Infrastructure\Activity\Services\ActivityPrivacy;
use App\Infrastructure\Collaboration\Events\CollaborativeFieldChanged;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Infrastructure\Collaboration\Models\FieldRevision;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    seedUserRolesAndPermissions();

    $this->role = Role::factory()->create(['name' => 'Support', 'description' => 'Helps users']);
});

/**
 * @return array{type: string, id: int, field: string}
 */
function roleField(Role $role, string $field): array
{
    return ['type' => 'role', 'id' => $role->id, 'field' => $field];
}

function claim(User $user, Role $role, string $field, string $tab = 'tab-1'): TestResponse
{
    return test()->actingAs($user)
        ->withHeader('X-Client-Id', $tab)
        ->postJson(route('collaboration.lock.store', roleField($role, $field)));
}

function saveField(User $user, Role $role, string $field, string $value, string $tab = 'tab-1'): TestResponse
{
    return test()->actingAs($user)
        ->withHeader('X-Client-Id', $tab)
        ->putJson(route('collaboration.value.update', roleField($role, $field)), ['value' => $value]);
}

function release(User $user, Role $role, string $field, string $tab = 'tab-1'): TestResponse
{
    return test()->actingAs($user)
        ->withHeader('X-Client-Id', $tab)
        ->deleteJson(route('collaboration.lock.destroy', roleField($role, $field)));
}

test('one person at a time edits a field; the same tab resumes', function () {
    $alice = createAdmin();
    $bob = createAdmin();

    claim($alice, $this->role, 'name')
        ->assertSuccessful()
        ->assertJsonPath('value', 'Support')
        ->assertJsonPath('lock.isThisTab', true);

    claim($alice, $this->role, 'name')->assertSuccessful();
    claim($alice, $this->role, 'name', 'tab-2')
        ->assertConflict()
        ->assertJsonPath('message', __('collaboration.locked_other_tab'));
    claim($bob, $this->role, 'name')
        ->assertConflict()
        ->assertJsonPath('message', __('collaboration.locked_by', ['name' => $alice->name]));

    // Another field of the same record is free.
    claim($bob, $this->role, 'description')->assertSuccessful();
});

test('only the holder saves, and autosave leaves the record version alone', function () {
    $alice = createAdmin();
    $bob = createAdmin();
    $version = $this->role->fresh()->version;

    saveField($alice, $this->role, 'name', 'Helpdesk')->assertConflict();

    claim($alice, $this->role, 'name');
    saveField($bob, $this->role, 'name', 'Hijacked')->assertConflict();
    saveField($alice, $this->role, 'name', 'Helpdesk')->assertOk()->assertJsonPath('value', 'Helpdesk');

    expect($this->role->fresh())
        ->name->toBe('Helpdesk')
        ->version->toBe($version)
        ->and(Activity::query()->forSubject($this->role)->count())->toBe(0);
});

test('the field rules apply to every save', function () {
    $admin = createAdmin();
    Role::factory()->create(['name' => 'Taken']);

    claim($admin, $this->role, 'name');

    saveField($admin, $this->role, 'name', '')->assertUnprocessable()->assertJsonValidationErrors('value');
    saveField($admin, $this->role, 'name', 'Taken')->assertUnprocessable()->assertJsonValidationErrors('value');
    saveField($admin, $this->role, 'name', '  Spaced  ')->assertOk();

    expect($this->role->fresh()->name)->toBe('  Spaced  ');

    claim($admin, $this->role, 'description');
    saveField($admin, $this->role, 'description', '')->assertOk();

    expect($this->role->fresh()->description)->toBeNull();
});

test('closing a session logs one change for all its saves', function () {
    Event::fake([CollaborativeFieldChanged::class]);
    $admin = createAdmin();

    claim($admin, $this->role, 'name');
    saveField($admin, $this->role, 'name', 'Help');
    saveField($admin, $this->role, 'name', 'Helpdesk');
    release($admin, $this->role, 'name')->assertNoContent();

    $activity = Activity::query()->forSubject($this->role)->sole();

    expect($activity)
        ->event->toBe(ActivityEvent::Updated)
        ->causer_id->toBe($admin->id)
        ->changes->toBe(['name' => ['old' => 'Support', 'new' => 'Helpdesk']])
        ->and(FieldLock::query()->count())->toBe(0)
        // A single line has no history of its own.
        ->and(FieldRevision::query()->count())->toBe(0);

    Event::assertDispatched(CollaborativeFieldChanged::class);
});

test('a session without changes logs nothing', function () {
    $admin = createAdmin();

    claim($admin, $this->role, 'name');
    saveField($admin, $this->role, 'name', 'Support');
    release($admin, $this->role, 'name');

    expect(Activity::query()->forSubject($this->role)->count())->toBe(0);
});

test('a long text keeps a revision per session, starting with the text from before', function () {
    $alice = createAdmin();
    $bob = createAdmin();

    claim($alice, $this->role, 'description');
    saveField($alice, $this->role, 'description', "Helps users\nand answers mail");
    release($alice, $this->role, 'description');

    claim($bob, $this->role, 'description');
    saveField($bob, $this->role, 'description', 'Answers mail');
    release($bob, $this->role, 'description');

    $revisions = FieldRevision::query()->orderBy('number')->get();

    expect($revisions->pluck('number')->all())->toBe([1, 2, 3])
        ->and($revisions->pluck('content')->all())->toBe(['Helps users', "Helps users\nand answers mail", 'Answers mail'])
        ->and($revisions->pluck('author_id')->all())->toBe([null, $alice->id, $bob->id]);

    $this->actingAs($alice)->getJson(route('collaboration.revisions.index', roleField($this->role, 'description')))
        ->assertOk()
        ->assertJsonCount(3)
        ->assertJsonPath('0.number', 3);

    $this->actingAs($alice)->getJson(route('collaboration.revisions.show', [...roleField($this->role, 'description'), 'number' => 1]))
        ->assertOk()
        ->assertJsonPath('content', 'Helps users')
        ->assertJsonPath('diffToCurrent.additions', 1)
        ->assertJsonPath('diffToCurrent.deletions', 1);
});

test('an earlier version is restored only while nobody edits the field', function () {
    $alice = createAdmin();
    $bob = createAdmin();

    claim($alice, $this->role, 'description');
    saveField($alice, $this->role, 'description', 'Rewritten');
    release($alice, $this->role, 'description');

    $restore = fn () => $this->actingAs($bob)
        ->postJson(route('collaboration.revisions.restore', [...roleField($this->role, 'description'), 'number' => 1]));

    claim($alice, $this->role, 'description');
    $restore()->assertConflict();
    release($alice, $this->role, 'description');

    $restore()->assertSuccessful()->assertJsonPath('value', 'Helps users');

    expect($this->role->fresh()->description)->toBe('Helps users')
        ->and(FieldRevision::query()->orderByDesc('number')->first())
        ->content->toBe('Helps users')
        ->restored_from_number->toBe(1)
        ->author_id->toBe($bob->id)
        ->and(Activity::query()->forSubject($this->role)->latest('id')->first())
        ->event->toBe(ActivityEvent::RevisionRestored)
        ->causer_id->toBe($bob->id);
});

test('an idle session is closed on behalf of its holder', function () {
    $admin = createAdmin();

    claim($admin, $this->role, 'name');
    saveField($admin, $this->role, 'name', 'Helpdesk');

    $this->travel(config('collaboration.idle_seconds') + 1)->seconds();
    $this->artisan('collaboration:close-expired-sessions')->assertSuccessful();

    expect(FieldLock::query()->count())->toBe(0)
        ->and(Activity::query()->forSubject($this->role)->sole())
        ->causer_id->toBe($admin->id)
        ->changes->toBe(['name' => ['old' => 'Support', 'new' => 'Helpdesk']]);

    // The old holder's save is refused; claiming again starts a new session.
    saveField($admin, $this->role, 'name', 'Late')->assertConflict();
    claim($admin, $this->role, 'name')->assertSuccessful()->assertJsonPath('value', 'Helpdesk');
});

test('a heartbeat keeps a session open past the connection grace', function () {
    $admin = createAdmin();

    claim($admin, $this->role, 'name');

    $this->travel(30)->seconds();
    $this->actingAs($admin)->withHeader('X-Client-Id', 'tab-1')
        ->putJson(route('collaboration.lock.update', roleField($this->role, 'name')))
        ->assertNoContent();
    $this->travel(30)->seconds();
    $this->artisan('collaboration:close-expired-sessions');

    expect(FieldLock::query()->count())->toBe(1);

    $this->travel(config('collaboration.connection_grace_seconds') + 1)->seconds();
    $this->artisan('collaboration:close-expired-sessions');

    expect(FieldLock::query()->count())->toBe(0);
});

test('fields are only open to whoever may see and edit the record', function () {
    $viewer = userWithPermissions(Permission::ViewUsers);

    claim($viewer, $this->role, 'name')->assertNotFound();
    saveField($viewer, $this->role, 'name', 'X')->assertNotFound();

    $admin = createAdmin();
    claim($admin, $this->role, 'unknown')->assertNotFound();
    $this->actingAs($admin)->postJson(route('collaboration.lock.store', ['type' => 'user', 'id' => $admin->id, 'field' => 'name']))
        ->assertNotFound();
    $this->actingAs($admin)->postJson(route('collaboration.lock.store', ['type' => 'role', 'id' => 999999, 'field' => 'name']))
        ->assertNotFound();
});

test('revisions are append-only and anonymized with their author', function () {
    $admin = createAdmin();

    claim($admin, $this->role, 'description');
    saveField($admin, $this->role, 'description', 'Rewritten');
    release($admin, $this->role, 'description');

    $revision = FieldRevision::query()->where('author_id', $admin->id)->sole();

    // In a savepoint: the refused statement aborts the transaction it runs in.
    expect(fn () => DB::transaction(fn () => $revision->forceFill(['content' => 'Forged'])->save()))
        ->toThrow(QueryException::class, 'append-only');

    app(ActivityPrivacy::class)->anonymizeUser($admin->id, 'user', 'Deleted user');

    expect($revision->fresh()->author_name)->toBe('Deleted user');
});

test('the role edit page offers name and description as collaborative fields', function () {
    $admin = createAdmin();

    claim($admin, $this->role, 'description', 'other-tab');

    $this->actingAs($admin)->withHeader('X-Client-Id', 'this-tab')->get(route('roles.edit', $this->role))
        ->assertInertia(fn ($page) => $page
            ->where('fields.name.value', 'Support')
            ->where('fields.name.multiline', false)
            ->where('fields.description.multiline', true)
            ->where('fields.description.lock.isMine', true)
            ->where('fields.description.lock.isThisTab', false));
});
