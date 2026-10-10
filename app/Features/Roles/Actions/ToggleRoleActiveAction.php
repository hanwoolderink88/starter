<?php

declare(strict_types=1);

namespace App\Features\Roles\Actions;

use App\Features\Roles\Events\RoleChanged;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Services\RoleService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;

/**
 * Deactivate an active role or reactivate an inactive one. Its assignments
 * stay either way: an inactive role grants nothing, and they work again as
 * soon as it is active.
 *
 * That the protected role may not be deactivated is a policy rule
 * (`RolePolicy::toggleActive`), checked by the caller.
 */
class ToggleRoleActiveAction
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    public function handle(Role $role, ChangeContext $context): Role
    {
        $wasActive = $role->is_active;

        $role = $this->roleService->toggleActive($role);

        $this->activityRecorder->record($context, ActivityEvent::StatusChanged, $role, ActivityRecorder::diff(
            ['is_active' => $wasActive],
            ['is_active' => $role->is_active],
        ));

        RoleChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Updated, $role->id, $role->name));

        return $role;
    }
}
