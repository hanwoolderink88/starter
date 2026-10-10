<?php

declare(strict_types=1);

namespace App\Features\Roles\Controllers;

use App\Features\Roles\Data\PermissionGroupData;
use App\Features\Roles\Data\RoleData;
use App\Features\Roles\Data\RoleFormPageData;
use App\Features\Roles\Data\RoleHolderData;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Services\RoleService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ActivityData;
use App\Infrastructure\Activity\Models\Activity;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EditRoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function __invoke(Role $role): Response
    {
        Gate::authorize('update', $role);

        return Inertia::render('roles/edit', new RoleFormPageData(
            role: RoleData::from($role),
            permissionGroups: PermissionGroupData::catalogue(),
            rolePermissions: $this->roleService->permissionsOf($role),
            activity: Gate::allows('viewAny', Activity::class)
                ? array_values(ActivityData::collect($this->roleService->timeline($role))->all())
                : null,
            holders: array_map(RoleHolderData::fromUser(...), $this->roleService->assignedUsers($role, RoleFormPageData::HOLDERS_LIMIT)),
        ));
    }
}
