<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Services;

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The only code that may change or remove activity entries, and only to
 * honour the privacy terms in config/privacy.php. Every method marks its own
 * transaction for the `activities_immutable` trigger; outside it the log stays
 * append-only.
 */
class ActivityPrivacy
{
    /**
     * Keys whose value is personal data wherever they appear in `changes`:
     * as a changed field, inside a snapshot, or as event details such as the
     * email address of a failed login.
     *
     * @var list<string>
     */
    public const array PERSONAL_FIELDS = [
        'name',
        'email',
    ];

    /**
     * Remove every trace of one user from the log: their name where they made
     * or impersonated a change, the network details of those requests, and
     * their personal fields in the entries about them. The entries stay, so
     * the log still proves what happened, but no longer who it was.
     *
     * @return int the number of entries changed
     */
    public function anonymizeUser(int $userId, string $morphAlias, string $label): int
    {
        return $this->maintain(function () use ($userId, $morphAlias, $label): int {
            $changed = Activity::query()->where('causer_id', $userId)->update([
                'causer_name' => $label,
                'ip_address' => null,
                'user_agent' => null,
            ]);

            $changed += Activity::query()->where('impersonator_id', $userId)->update([
                'impersonator_name' => $label,
                'ip_address' => null,
                'user_agent' => null,
            ]);

            Activity::query()
                ->where('subject_type', $morphAlias)
                ->where('subject_id', $userId)
                ->get()
                ->each(function (Activity $activity) use ($label, &$changed): void {
                    $scrubbed = $this->scrub($activity->changes, $label);

                    if ($scrubbed !== $activity->changes) {
                        Activity::query()->whereKey($activity->id)->update(['changes' => json_encode($scrubbed, JSON_THROW_ON_ERROR)]);
                        $changed++;
                    }
                });

            return $changed;
        });
    }

    /**
     * Clear IP addresses and user agents of entries before the moment, and
     * the email addresses typed into failed logins and lockouts for accounts
     * that do not exist (those have no user to anonymize later).
     *
     * @return int the number of entries changed
     */
    public function clearNetworkDetailsBefore(CarbonImmutable $before): int
    {
        return $this->maintain(function () use ($before): int {
            $changed = Activity::query()
                ->where('created_at', '<', $before)
                ->where(fn ($query) => $query->whereNotNull('ip_address')->orWhereNotNull('user_agent'))
                ->update(['ip_address' => null, 'user_agent' => null]);

            Activity::query()
                ->where('created_at', '<', $before)
                ->whereNull('subject_id')
                ->whereIn('event', [ActivityEvent::LoginFailed, ActivityEvent::LockedOut])
                ->whereNotNull('changes->email')
                ->get()
                ->each(function (Activity $activity) use (&$changed): void {
                    Activity::query()->whereKey($activity->id)->update([
                        'changes' => json_encode(['email' => null], JSON_THROW_ON_ERROR),
                    ]);
                    $changed++;
                });

            return $changed;
        });
    }

    /**
     * Delete the entries before the moment: the retention term.
     *
     * @return int the number of entries deleted
     */
    public function deleteBefore(CarbonImmutable $before): int
    {
        return $this->maintain(fn (): int => Activity::query()->where('created_at', '<', $before)->delete());
    }

    /**
     * Replace the value of every personal field, at any depth, with the label.
     * An old/new pair keeps its shape, so the entry still shows that the field
     * changed.
     *
     * @param  array<array-key, mixed>  $changes
     * @return array<array-key, mixed>
     */
    private function scrub(array $changes, string $label): array
    {
        foreach ($changes as $key => $value) {
            if (is_string($key) && in_array($key, self::PERSONAL_FIELDS, true)) {
                $changes[$key] = is_array($value)
                    ? array_map(fn (mixed $part): mixed => $part === null ? null : $label, $value)
                    : ($value === null ? null : $label);
            } elseif (is_array($value)) {
                $changes[$key] = $this->scrub($value, $label);
            }
        }

        return $changes;
    }

    /**
     * Run the callback with the trigger's permission. `SET LOCAL` lasts until
     * the outermost transaction ends, also when this one is nested (a
     * savepoint), so the permission is switched off again as soon as the
     * maintenance is done.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function maintain(callable $callback): mixed
    {
        return DB::transaction(function () use ($callback): mixed {
            DB::statement("SET LOCAL app.activity_privacy_maintenance = 'on'");

            try {
                return $callback();
            } finally {
                DB::statement("SET LOCAL app.activity_privacy_maintenance = 'off'");
            }
        });
    }
}
