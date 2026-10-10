<?php

declare(strict_types=1);

namespace App\Features\Roles\Actions;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Services\RoleService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Bring the database in line with the permission enum: create the missing
 * permissions, and make sure the protected administrator role exists and has
 * every permission. Idempotent; runs in the seeder and on every deploy
 * (`permissions:sync`). Only real changes are logged.
 */
class SyncPermissionsAction
{
    public const string ADMINISTRATOR_NAME = 'Administrator';

    public const string ADMINISTRATOR_DESCRIPTION = 'Manages users, roles and the application.';

    public function __construct(
        private readonly RoleService $roleService,
        private readonly ActivityRecorder $activityRecorder,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function handle(ChangeContext $context): Role
    {
        $this->permissionRegistrar->forgetCachedPermissions();

        $this->roleService->createMissingPermissions();

        $administrator = $this->roleService->firstOrCreateProtected(self::ADMINISTRATOR_NAME, self::ADMINISTRATOR_DESCRIPTION);
        $before = $administrator->wasRecentlyCreated ? null : $this->roleService->permissionNames($administrator);

        $this->roleService->givePermissions($administrator, Permission::cases());
        $after = $this->roleService->permissionNames($administrator);

        if ($before === null) {
            $this->activityRecorder->record($context, ActivityEvent::Created, $administrator, ActivityRecorder::diff([], $this->roleService->activityAttributes($administrator)));
        } elseif ($before !== $after) {
            $this->activityRecorder->record($context, ActivityEvent::Updated, $administrator, ActivityRecorder::diff(
                ['permissions' => $before],
                ['permissions' => $after],
            ));
        }

        $this->permissionRegistrar->forgetCachedPermissions();

        return $administrator;
    }
}
