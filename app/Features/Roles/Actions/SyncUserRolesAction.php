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
 * Make the user's active roles exactly this set, keeping the assignments of
 * inactive roles: those are only removed explicitly, through
 * RevokeUserRoleAction.
 *
 * A set equal to the current active set is no change: nothing is written or
 * logged. Whether the actor may change the set at all (`manage roles`) is the
 * caller's `UserPolicy::assignRoles` check. The user's own signal is sent by
 * the calling UserManagement Action.
 */
class SyncUserRolesAction
{
    public function __construct(
        private readonly RoleAssignmentService $roleAssignmentService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * @param  list<int>  $roleIds  validated ids of active roles
     * @return bool whether the set changed
     *
     * @throws LastAdministratorException when the last administrator would lose the role
     */
    public function handle(User $user, array $roleIds, ChangeContext $context): bool
    {
        $requested = $this->roleAssignmentService->activeAmong($roleIds);

        if ($requested === $this->roleAssignmentService->activeRoleIdsOf($user)) {
            return false;
        }

        if (! in_array($this->roleAssignmentService->administratorRoleId(), $requested, true) && $this->roleAssignmentService->isLastAdministrator($user)) {
            throw new LastAdministratorException;
        }

        $before = $this->roleAssignmentService->roleNamesOf($user);

        $this->roleAssignmentService->syncActiveRoles($user, $requested);

        $this->activityRecorder->record($context, ActivityEvent::RolesChanged, $user, ActivityRecorder::diff(
            ['roles' => $before],
            ['roles' => $this->roleAssignmentService->roleNamesOf($user)],
        ));

        return true;
    }
}
