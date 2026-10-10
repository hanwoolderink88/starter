<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Actions;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Events\CollaborativeFieldChanged;
use App\Infrastructure\Collaboration\Exceptions\FieldLockedException;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Infrastructure\Collaboration\Services\CollaborativeFields;
use App\Infrastructure\Collaboration\Services\FieldLockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use LogicException;

/**
 * Start editing a field: one person at a time. The same user in the same tab
 * resumes their session; anyone else, or the same user in another tab, is
 * refused with who holds it. An expired session is closed first, on behalf of
 * its holder. That the user may edit the record is the caller's policy check.
 */
class ClaimFieldAction
{
    public function __construct(
        private readonly FieldLockService $fieldLockService,
        private readonly CollaborativeFields $collaborativeFields,
        private readonly CloseFieldSessionAction $closeFieldSessionAction,
    ) {}

    /**
     * @param  CollaborativeModel&Model  $model
     *
     * @throws FieldLockedException when someone else, or the same user in another tab, holds the field
     */
    public function handle(CollaborativeModel $model, string $field, ChangeContext $context): FieldLock
    {
        $actor = $context->actor ?? throw new LogicException('Only a user can claim a field.');

        $lock = DB::transaction(function () use ($model, $field, $context, $actor): ?FieldLock {
            $lock = $this->fieldLockService->lockRecord($model, $field);

            if ($lock instanceof FieldLock && ! $lock->isExpired()) {
                if ($context->origin !== null && $lock->isHeldBy($actor, $context->origin)) {
                    $this->fieldLockService->heartbeat($lock);

                    return null;
                }

                throw FieldLockedException::heldBy($lock, $actor);
            }

            if ($lock instanceof FieldLock) {
                $this->closeFieldSessionAction->handle($model, $field, $lock);
            }

            try {
                return DB::transaction(fn (): FieldLock => $this->fieldLockService->create(
                    $model,
                    $field,
                    $actor,
                    $context->source,
                    $context->origin,
                    $context->impersonator,
                    $this->collaborativeFields->value($model, $field),
                ));
            } catch (UniqueConstraintViolationException) {
                $winner = $this->fieldLockService->find($model, $field);

                throw $winner instanceof FieldLock ? FieldLockedException::heldBy($winner, $actor) : new LogicException('The field lock vanished.');
            }
        });

        if ($lock instanceof FieldLock) {
            $key = $model->getKey();

            Event::dispatch(new CollaborativeFieldChanged(
                ResourceChangedData::fromContext($context, ResourceAction::Updated, is_int($key) ? $key : (int) $key, $field),
                $model->collaborationChannels(),
            ));

            return $lock;
        }

        return $this->fieldLockService->find($model, $field) ?? throw new LogicException('The field lock vanished.');
    }
}
