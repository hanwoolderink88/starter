<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Actions;

use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Services\FieldLockService;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Close every session that ended without a goodbye (a closed laptop, a lost
 * connection, too long without typing), on behalf of its holder.
 */
class CloseExpiredFieldSessionsAction
{
    public function __construct(
        private readonly FieldLockService $fieldLockService,
        private readonly CloseFieldSessionAction $closeFieldSessionAction,
    ) {}

    /**
     * @return int the number of sessions closed
     */
    public function handle(): int
    {
        $closed = 0;

        foreach ($this->fieldLockService->expired() as $lock) {
            $class = Relation::getMorphedModel($lock->editable_type);
            $model = $class === null ? null : $class::query()->withoutGlobalScopes()->find($lock->editable_id);

            if (! $model instanceof CollaborativeModel) {
                $this->fieldLockService->delete($lock);

                continue;
            }

            $this->closeFieldSessionAction->handle($model, $lock->field, $lock);
            $closed++;
        }

        return $closed;
    }
}
