<?php

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;

test('an entry snapshots who did it, through which surface and from where', function () {
    $actor = User::factory()->create(['name' => 'Jane']);
    $impersonator = User::factory()->create(['name' => 'Piet']);
    $subject = User::factory()->create();

    $context = new ChangeContext(
        actor: $actor,
        source: ActivitySource::Web,
        origin: 'tab-1',
        impersonator: $impersonator,
        ipAddress: '203.0.113.7',
        userAgent: 'Firefox',
    );

    $activity = app(ActivityRecorder::class)->record($context, ActivityEvent::Updated, $subject, ['name' => ['old' => 'A', 'new' => 'B']]);

    expect($activity->fresh())
        ->subject_type->toBe('user')
        ->subject_id->toBe($subject->id)
        ->causer_id->toBe($actor->id)
        ->causer_name->toBe('Jane')
        ->impersonator_id->toBe($impersonator->id)
        ->impersonator_name->toBe('Piet')
        ->source->toBe(ActivitySource::Web)
        ->ip_address->toBe('203.0.113.7')
        ->user_agent->toBe('Firefox')
        ->changes->toBe(['name' => ['old' => 'A', 'new' => 'B']]);
});

test('a system change names the system and has no request details', function () {
    $activity = app(ActivityRecorder::class)->record(ChangeContext::system(), ActivityEvent::Created, User::factory()->create());

    expect($activity->fresh())
        ->causer_id->toBeNull()
        ->causer_name->toBe('System')
        ->source->toBe(ActivitySource::System)
        ->ip_address->toBeNull();
});

test('secrets never enter the log, at any depth', function () {
    $activity = app(ActivityRecorder::class)->record(ChangeContext::system(), ActivityEvent::Deleted, User::factory()->create(), [
        'name' => ['old' => 'A', 'new' => 'B'],
        'password' => ['old' => 'x', 'new' => 'y'],
        'api_token' => ['old' => 'x', 'new' => 'y'],
        'snapshot' => ['email' => 'a@example.com', 'two_factor_secret' => 's', 'remember_token' => 't', 'client_secret' => 'c'],
    ]);

    expect($activity->fresh()->changes)->toBe([
        'name' => ['old' => 'A', 'new' => 'B'],
        'snapshot' => ['email' => 'a@example.com'],
    ]);
});

test('a diff holds only the fields that changed', function () {
    expect(ActivityRecorder::diff(
        ['name' => 'A', 'email' => 'a@example.com', 'role' => 'user'],
        ['name' => 'B', 'email' => 'a@example.com', 'role' => 'user'],
    ))->toBe(['name' => ['old' => 'A', 'new' => 'B']]);
});
