<?php

declare(strict_types=1);

namespace App\Features\Roles\Actions;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Events\RoleChanged;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Services\RoleService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Exceptions\StaleVersionException;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use Illuminate\Support\Facades\DB;

class UpdateRoleAction
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * The permissions are logged with their old and new value. The name and
     * description are collaborative fields with their own sessions. The
     * protected administrator role always keeps every permission, whatever
     * is asked.
     *
     * @param  int  $version  the version the change is based on
     * @param  list<Permission>  $permissions
     *
     * @throws StaleVersionException when someone saved the role in the meantime
     */
    public function handle(Role $role, int $version, array $permissions, ChangeContext $context): Role
    {
        if ($role->is_protected) {
            $permissions = Permission::cases();
        }

        $role = DB::transaction(function () use ($role, $version, $permissions, $context): Role {
            $before = $this->loggedFields($role);

            $role = $this->roleService->updatePermissions($role, $version, $permissions);

            $changes = ActivityRecorder::diff($before, $this->loggedFields($role));

            if ($changes !== []) {
                $this->activityRecorder->record($context, ActivityEvent::Updated, $role, $changes);
            }

            return $role;
        });

        RoleChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Updated, $role->id, $role->name));

        return $role;
    }

    /**
     * @return array{permissions: list<string>}
     */
    private function loggedFields(Role $role): array
    {
        return [
            'permissions' => $this->roleService->permissionNames($role),
        ];
    }
}
