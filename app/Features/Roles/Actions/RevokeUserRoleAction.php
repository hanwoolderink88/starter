<?php

declare(strict_types=1);

namespace App\Features\Roles\Actions;

use App\Features\Roles\Exceptions\LastAdministratorException;
use App\Features\Roles\Services\RoleAssignmentService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;

/**
 * Take one role away from a user, also when the role is inactive. This is the
 * only way an inactive assignment is removed: the sync always keeps it, so a
 * form that leaves an inactive id out never revokes it by accident.
 *
 * Whether the actor may do this (`manage roles`) is the caller's check.
 */
class RevokeUserRoleAction
{
    public function __construct(
        private readonly RoleAssignmentService $roleAssignmentService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * @return bool whether the user had the role
     *
     * @throws LastAdministratorException when the last administrator would lose the role
     */
    public function handle(User $user, int $roleId, ChangeContext $context): bool
    {
        if (! $this->roleAssignmentService->hasRole($user, $roleId)) {
            return false;
        }

        if ($roleId === $this->roleAssignmentService->administratorRoleId() && $this->roleAssignmentService->isLastAdministrator($user)) {
            throw new LastAdministratorException;
        }

        $before = $this->roleAssignmentService->roleNamesOf($user);

        $this->roleAssignmentService->revokeRole($user, $roleId);

        $this->activityRecorder->record($context, ActivityEvent::RolesChanged, $user, ActivityRecorder::diff(
            ['roles' => $before],
            ['roles' => $this->roleAssignmentService->roleNamesOf($user)],
        ));

        return true;
    }
}
