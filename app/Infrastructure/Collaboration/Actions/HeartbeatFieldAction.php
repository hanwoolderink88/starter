<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Actions;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Exceptions\FieldNotHeldException;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Infrastructure\Collaboration\Services\FieldLockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The editor is still open: keep the field held.
 */
class HeartbeatFieldAction
{
    public function __construct(
        private readonly FieldLockService $fieldLockService,
    ) {}

    /**
     * @param  CollaborativeModel&Model  $model
     *
     * @throws FieldNotHeldException when this user and tab do not hold the field (any more)
     */
    public function handle(CollaborativeModel $model, string $field, ChangeContext $context): void
    {
        DB::transaction(function () use ($model, $field, $context): void {
            $lock = $this->fieldLockService->lockRecord($model, $field);

            if (! $lock instanceof FieldLock || $lock->isExpired() || ! $lock->isHeldBy($context->actor, $context->origin)) {
                throw new FieldNotHeldException;
            }

            $this->fieldLockService->heartbeat($lock);
        });
    }
}
