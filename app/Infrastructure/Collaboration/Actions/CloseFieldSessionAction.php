<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Actions;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Events\CollaborativeFieldChanged;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Infrastructure\Collaboration\Services\CollaborativeFields;
use App\Infrastructure\Collaboration\Services\FieldLockService;
use App\Infrastructure\Collaboration\Services\FieldRevisionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * End an editing session: remove the lock and, when the value changed during
 * it, log one change with the value before and after the session. A long
 * text also gets a revision. The many autosaves of one session are one entry,
 * never one per keystroke.
 *
 * A long text without revisions yet first gets one of the value it had
 * before collaboration began, so its history is complete.
 */
class CloseFieldSessionAction
{
    public function __construct(
        private readonly FieldLockService $fieldLockService,
        private readonly FieldRevisionService $fieldRevisionService,
        private readonly CollaborativeFields $collaborativeFields,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * @param  CollaborativeModel&Model  $model
     * @param  FieldLock  $lock  the lock whose session to close; nothing happens when it is gone already
     * @param  ChangeContext|null  $context  the holder's own request when they leave; null when the session expired, then it is closed on their behalf
     */
    public function handle(CollaborativeModel $model, string $field, FieldLock $lock, ?ChangeContext $context = null): void
    {
        $context ??= ChangeContext::forActor($lock->user, $lock->source, $lock->impersonator);

        $closed = DB::transaction(function () use ($model, $field, $lock, $context): bool {
            $current = $this->fieldLockService->lockRecord($model, $field);

            if ($current === null || $current->id !== $lock->id) {
                return false;
            }

            $this->fieldLockService->delete($current);

            $before = $current->base_value ?? '';
            $after = $this->collaborativeFields->value($model, $field);

            if ($before === $after) {
                return true;
            }

            if ($this->collaborativeFields->definition($model, $field)?->multiline === true) {
                if ($before !== '' && $this->fieldRevisionService->latest($model, $field) === null) {
                    $this->fieldRevisionService->append($model, $field, $before, ChangeContext::system(), null);
                }

                $this->fieldRevisionService->append($model, $field, $after, $context, $current->claimed_at);
            }

            $this->activityRecorder->record($context, ActivityEvent::Updated, $model, [
                $field => ['old' => $before, 'new' => $after],
            ]);

            return true;
        });

        if ($closed) {
            $key = $model->getKey();

            Event::dispatch(new CollaborativeFieldChanged(
                ResourceChangedData::fromContext($context, ResourceAction::Updated, is_int($key) ? $key : (int) $key, $field),
                $model->collaborationChannels(),
            ));
        }
    }
}
