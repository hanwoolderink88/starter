<?php

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;

test('network details are cleared after their term, the entries stay', function () {
    $old = Activity::factory()->create(['created_at' => now()->subDays(91)]);
    $recent = Activity::factory()->create(['created_at' => now()->subDays(10)]);

    $this->artisan('activity:prune')->assertSuccessful();

    expect($old->fresh())->ip_address->toBeNull()->user_agent->toBeNull()
        ->and($recent->fresh())->ip_address->not->toBeNull()
        ->and(Activity::query()->count())->toBe(2);
});

test('an email address typed into a failed login for an unknown account is cleared after the term', function () {
    $entry = Activity::factory()->create([
        'subject_type' => null,
        'subject_id' => null,
        'event' => ActivityEvent::LoginFailed,
        'changes' => ['email' => 'someone@example.com'],
        'created_at' => now()->subDays(91),
    ]);

    $this->artisan('activity:prune')->assertSuccessful();

    expect($entry->fresh()->changes)->toBe(['email' => null]);
});

test('entries are kept forever unless a retention term is set', function () {
    $ancient = Activity::factory()->create(['created_at' => now()->subYears(10)]);

    $this->artisan('activity:prune')->assertSuccessful();
    expect($ancient->fresh())->not->toBeNull();

    config(['privacy.activity_retention_days' => 365 * 7]);
    $this->artisan('activity:prune')->assertSuccessful();

    expect(Activity::query()->whereKey($ancient->id)->exists())->toBeFalse();
});

test('the privacy commands run every day', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('users:anonymize')
        ->expectsOutputToContain('activity:prune')
        ->assertSuccessful();
});
