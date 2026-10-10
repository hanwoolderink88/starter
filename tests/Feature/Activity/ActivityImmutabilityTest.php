<?php

use App\Infrastructure\Activity\Models\Activity;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// Each attempt runs in its own transaction (a savepoint): a refused statement
// aborts the transaction it runs in, which would fail every later statement
// for the wrong reason.

test('an entry cannot be changed', function () {
    $activity = Activity::factory()->create(['causer_name' => 'Jane']);

    expect(fn () => DB::transaction(fn () => DB::table('activities')->where('id', $activity->id)->update(['causer_name' => 'Someone else'])))
        ->toThrow(QueryException::class, 'append-only');

    expect($activity->fresh()->causer_name)->toBe('Jane');
});

test('an entry cannot be deleted', function () {
    $activity = Activity::factory()->create();

    expect(fn () => DB::transaction(fn () => DB::table('activities')->where('id', $activity->id)->delete()))
        ->toThrow(QueryException::class, 'append-only');

    expect(Activity::query()->whereKey($activity->id)->exists())->toBeTrue();
});
