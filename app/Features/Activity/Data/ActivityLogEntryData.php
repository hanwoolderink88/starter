<?php

declare(strict_types=1);

namespace App\Features\Activity\Data;

use App\Features\Roles\Models\Role;
use App\Infrastructure\Activity\Data\ActivityData;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A log entry together with the name of what it is about: the subject's
 * current name, or the name it had when it was deleted.
 */
#[TypeScript]
class ActivityLogEntryData extends Data
{
    public function __construct(
        public ActivityData $activity,
        public ?string $subjectName,
        /** Whether the subject still exists, so the page can link to it. */
        public bool $subjectExists,
    ) {}

    public static function fromActivity(Activity $activity): self
    {
        $subject = $activity->subject;
        $snapshotName = $activity->changes['snapshot']['name'] ?? null;

        return new self(
            activity: ActivityData::fromActivity($activity),
            subjectName: $subject instanceof User || $subject instanceof Role ? $subject->name : (is_string($snapshotName) ? $snapshotName : null),
            subjectExists: $subject !== null && ! ($subject instanceof User && $subject->trashed()),
        );
    }
}
