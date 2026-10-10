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
use App\Infrastructure\Collaboration\Exceptions\FieldLockedException;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Infrastructure\Collaboration\Models\FieldRevision;
use App\Infrastructure\Collaboration\Services\CollaborativeFields;
use App\Infrastructure\Collaboration\Services\FieldLockService;
use App\Infrastructure\Collaboration\Services\FieldRevisionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Put an older text back. History is never rewritten: the restore is a new
 * revision that says which one it came from, and it is logged. Only a free
 * field can be restored; an expired session is closed first, on behalf of its
 * holder, so its last autosave becomes a revision before it is replaced.
 */
class RestoreFieldRevisionAction
{
    public function __construct(
        private readonly FieldLockService $fieldLockService,
        private readonly FieldRevisionService $fieldRevisionService,
        private readonly CollaborativeFields $collaborativeFields,
        private readonly CloseFieldSessionAction $closeFieldSessionAction,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * @param  CollaborativeModel&Model  $model
     *
     * @throws ModelNotFoundException when the field has no revision with this number
     * @throws FieldLockedException when someone holds the field
     */
    public function handle(CollaborativeModel $model, string $field, int $number, ChangeContext $context): void
    {
        DB::transaction(function () use ($model, $field, $number, $context): void {
            $lock = $this->fieldLockService->lockRecord($model, $field);

            if ($lock instanceof FieldLock && ! $lock->isExpired()) {
                throw FieldLockedException::heldBy($lock, $context->actor);
            }

            if ($lock instanceof FieldLock) {
                $this->closeFieldSessionAction->handle($model, $field, $lock);
            }

            $restored = $this->fieldRevisionService->find($model, $field, $number)
                ?? throw (new ModelNotFoundException)->setModel(FieldRevision::class, [$number]);

            $before = $this->collaborativeFields->value($model, $field);

            $this->collaborativeFields->write($model, $field, $restored->content);
            $this->fieldRevisionService->append($model, $field, $restored->content, $context, null, $restored->number);

            $this->activityRecorder->record($context, ActivityEvent::RevisionRestored, $model, [
                $field => ['old' => $before, 'new' => $restored->content],
                'restored_from' => ['old' => null, 'new' => $restored->number],
            ]);
        });

        $key = $model->getKey();

        Event::dispatch(new CollaborativeFieldChanged(
            ResourceChangedData::fromContext($context, ResourceAction::Updated, is_int($key) ? $key : (int) $key, $field),
            $model->collaborationChannels(),
        ));
    }
}
