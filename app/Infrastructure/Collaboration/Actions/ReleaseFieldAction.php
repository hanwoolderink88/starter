<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Actions;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Infrastructure\Collaboration\Services\FieldLockService;
use Illuminate\Database\Eloquent\Model;

/**
 * The holder is done with the field: close their session now. Releasing a
 * field this tab does not hold does nothing, so a late release after the
 * session expired is harmless.
 */
class ReleaseFieldAction
{
    public function __construct(
        private readonly FieldLockService $fieldLockService,
        private readonly CloseFieldSessionAction $closeFieldSessionAction,
    ) {}

    /**
     * @param  CollaborativeModel&Model  $model
     */
    public function handle(CollaborativeModel $model, string $field, ChangeContext $context): void
    {
        $lock = $this->fieldLockService->find($model, $field);

        if ($lock instanceof FieldLock && $lock->isHeldBy($context->actor, $context->origin)) {
            $this->closeFieldSessionAction->handle($model, $field, $lock, $context);
        }
    }
}
