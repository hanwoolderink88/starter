<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Actions;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Events\CollaborativeFieldChanged;
use App\Infrastructure\Collaboration\Exceptions\FieldNotHeldException;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Infrastructure\Collaboration\Services\CollaborativeFields;
use App\Infrastructure\Collaboration\Services\FieldLockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use LogicException;

/**
 * An autosave by the holder. Only the tab that holds the field may save, so
 * nobody overwrites anyone. An autosave is not logged on its own: the session
 * is, once it closes.
 */
class SaveFieldAction
{
    public function __construct(
        private readonly FieldLockService $fieldLockService,
        private readonly CollaborativeFields $collaborativeFields,
    ) {}

    /**
     * @param  CollaborativeModel&Model  $model
     *
     * @throws FieldNotHeldException when this user and tab do not hold the field (any more)
     * @throws ValidationException on `value` when the field's rules refuse it
     */
    public function handle(CollaborativeModel $model, string $field, string $value, ChangeContext $context): void
    {
        $definition = $this->collaborativeFields->definition($model, $field) ?? throw new LogicException("[{$field}] is not a collaborative field.");

        $this->collaborativeFields->validate($definition, $value);

        DB::transaction(function () use ($model, $field, $value, $context): void {
            $lock = $this->fieldLockService->lockRecord($model, $field);

            if (! $lock instanceof FieldLock || $lock->isExpired() || ! $lock->isHeldBy($context->actor, $context->origin)) {
                throw new FieldNotHeldException;
            }

            $this->collaborativeFields->write($model, $field, $value);
            $this->fieldLockService->recordEdit($lock);
        });

        $key = $model->getKey();

        Event::dispatch(new CollaborativeFieldChanged(
            ResourceChangedData::fromContext($context, ResourceAction::Updated, is_int($key) ? $key : (int) $key, $field),
            $model->collaborationChannels(),
        ));
    }
}
