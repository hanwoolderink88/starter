<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Exceptions;

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * A save based on a version that is no longer current.
 *
 * It is a validation error on the field `version`, so the web gets a normal
 * error at `errors.version` and an MCP tool returns the same text. The message
 * names who changed the record and when (the latest change in the activity
 * log) and the current version, so a person can reload and an agent can retry.
 */
class StaleVersionException extends ValidationException
{
    /**
     * @param  Model  $model  the record as it is now in the database
     * @param  string  $messageKey  prefix of `{prefix}.changed_by` and `{prefix}.changed`
     */
    public static function for(Model $model, string $messageKey): self
    {
        $latestChange = Activity::query()
            ->forSubject($model)
            ->whereIn('event', [ActivityEvent::Updated, ActivityEvent::RolesChanged, ActivityEvent::StatusChanged])
            ->first();

        $version = $model->getAttribute('version');
        $changedAt = $latestChange?->created_at;

        // A change that is not logged (a user's own profile) happened after
        // the latest logged one; then that entry does not tell who it was.
        $updatedAt = $model->getAttribute('updated_at');
        $known = $latestChange !== null
            && $changedAt instanceof CarbonInterface
            && ! ($updatedAt instanceof DateTimeInterface && $changedAt->lessThan($updatedAt));

        if (! $known) {
            return self::withMessages([
                'version' => __($messageKey.'.changed', ['version' => $version]),
            ]);
        }

        return self::withMessages([
            'version' => __($messageKey.'.changed_by', [
                'name' => $latestChange->causer_name,
                'time' => $changedAt->isoFormat('lll'),
                'version' => $version,
            ]),
        ]);
    }
}
