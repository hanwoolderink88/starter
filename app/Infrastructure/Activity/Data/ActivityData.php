<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Data;

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Activity\Models\Activity;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One log entry as the frontend shows it: in a timeline on the subject, or in
 * the activity log. The label for `event` lives under `activity.events.*`.
 */
#[TypeScript]
class ActivityData extends Data
{
    /**
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>|null  $snapshot
     */
    public function __construct(
        public int $id,
        public ActivityEvent $event,
        public ?string $subjectType,
        public ?int $subjectId,
        public ?int $causerId,
        public string $causerName,
        public ?string $impersonatorName,
        public ActivitySource $source,
        /** Per changed field the old and the new value, or the details of an event (such as the email address of a failed login). */
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $changes,
        /** The full state of a deleted record, otherwise null. */
        #[LiteralTypeScriptType('Record<string, unknown> | null')]
        public ?array $snapshot,
        public ?string $ipAddress,
        public ?string $userAgent,
        public string $createdAt,
        /** Formatted on the server, to the second, so SSR and the browser agree. */
        public string $createdAtDisplay,
    ) {}

    public static function fromActivity(Activity $activity): self
    {
        $changes = $activity->changes;
        $snapshot = $changes['snapshot'] ?? null;

        return new self(
            id: $activity->id,
            event: $activity->event,
            subjectType: $activity->subject_type,
            subjectId: $activity->subject_id,
            causerId: $activity->causer_id,
            causerName: $activity->causer_name,
            impersonatorName: $activity->impersonator_name,
            source: $activity->source,
            changes: is_array($snapshot) ? [] : $changes,
            snapshot: is_array($snapshot) ? $snapshot : null,
            ipAddress: $activity->ip_address,
            userAgent: $activity->user_agent,
            createdAt: $activity->created_at->toISOString() ?? '',
            createdAtDisplay: $activity->created_at->format('Y-m-d H:i:s'),
        );
    }
}
