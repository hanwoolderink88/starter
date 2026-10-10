<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Services;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Models\Activity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Writes the activity log. Actions call it with the ChangeContext they were
 * given; the log is never written from models, observers or controllers.
 */
class ActivityRecorder
{
    /**
     * Fields that never enter `changes`, at any depth. Besides these, every key
     * ending in `_token` or `_secret` is dropped too.
     *
     * @var list<string>
     */
    public const array SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @param  Model|null  $subject  null only when there is nothing to point at, such as a failed login for an unknown email address
     * @param  array<string, mixed>  $changes  per-field changes from diff(), a snapshot from snapshot(), or event details
     */
    public function record(ChangeContext $context, ActivityEvent $event, ?Model $subject, array $changes = []): Activity
    {
        $subjectId = $subject?->getKey();

        return Activity::create([
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subjectId === null ? null : (int) $subjectId,
            'causer_id' => $context->actor?->id,
            'causer_name' => $context->causerName(),
            'impersonator_id' => $context->impersonator?->id,
            'impersonator_name' => $context->impersonator?->name,
            'source' => $context->source,
            'event' => $event,
            'changes' => $this->withoutSensitiveFields($changes),
            'ip_address' => $context->ipAddress,
            'user_agent' => $context->userAgent === null ? null : Str::limit($context->userAgent, 500, ''),
        ]);
    }

    /**
     * The fields whose value differs between two states, as old and new value.
     * A field missing on one side counts as null there.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{old: mixed, new: mixed}>
     */
    public static function diff(array $before, array $after): array
    {
        $changes = [];

        foreach (array_keys($before + $after) as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;

            if ($old !== $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }

    /**
     * The full state of a record that is deleted, so the log still shows what
     * it was.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{snapshot: array<string, mixed>}
     */
    public static function snapshot(array $attributes): array
    {
        return ['snapshot' => $attributes];
    }

    public static function isSensitive(string $field): bool
    {
        return in_array($field, self::SENSITIVE_FIELDS, true)
            || Str::endsWith($field, ['_token', '_secret']);
    }

    /**
     * @param  array<array-key, mixed>  $changes
     * @return array<array-key, mixed>
     */
    private function withoutSensitiveFields(array $changes): array
    {
        $filtered = [];

        foreach ($changes as $key => $value) {
            if (is_string($key) && self::isSensitive($key)) {
                continue;
            }

            $filtered[$key] = is_array($value) ? $this->withoutSensitiveFields($value) : $value;
        }

        return $filtered;
    }
}
