<?php

declare(strict_types=1);

namespace App\Features\Roles\Actions;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Events\RoleChanged;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Services\RoleService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use Illuminate\Support\Facades\DB;

class CreateRoleAction
{
    public function __construct(
        private readonly RoleService $roleService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    /**
     * @param  list<Permission>  $permissions
     */
    public function handle(string $name, ?string $description, array $permissions, ChangeContext $context): Role
    {
        $role = DB::transaction(function () use ($name, $description, $permissions, $context): Role {
            $role = $this->roleService->store($name, $description, $permissions);

            $this->activityRecorder->record($context, ActivityEvent::Created, $role, ActivityRecorder::diff([], $this->roleService->activityAttributes($role)));

            return $role;
        });

        RoleChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Created, $role->id, $role->name));

        return $role;
    }
}
