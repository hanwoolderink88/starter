<?php

declare(strict_types=1);

namespace App\Features\Roles\Actions;

use App\Features\Roles\Events\RoleChanged;
use App\Features\Roles\Exceptions\RoleStillAssignedException;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Services\RoleService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use Illuminate\Support\Facades\DB;

/**
 * Delete a role for good. A role is configuration, so this is a hard delete
 * and the log keeps a full snapshot. A role that someone still has stays.
 *
 * That the protected role may not be deleted is a policy rule
 * (`RolePolicy::delete`), checked by the caller.
 */
class DeleteRoleAction
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * @throws RoleStillAssignedException when a user still has it
     */
    public function handle(Role $role, ChangeContext $context): void
    {
        if ($this->roleService->isAssigned($role)) {
            throw new RoleStillAssignedException;
        }

        DB::transaction(function () use ($role, $context): void {
            $this->activityRecorder->record($context, ActivityEvent::Deleted, $role, ActivityRecorder::snapshot($this->roleService->activityAttributes($role)));

            $this->roleService->delete($role);
        });

        RoleChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Deleted, $role->id, $role->name));
    }
}
