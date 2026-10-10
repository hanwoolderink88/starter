<?php

declare(strict_types=1);

namespace App\Features\Roles\Data;

use App\Features\Roles\Enums\Permission;
use App\Infrastructure\Activity\Data\ActivityData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class RoleFormPageData extends Data
{
    /**
     * How many holders the delete dialog names before "and n more".
     */
    public const int HOLDERS_LIMIT = 10;

    /**
     * @param  RoleData|null  $role  null when creating
     * @param  list<PermissionGroupData>  $permissionGroups  the whole catalogue
     * @param  list<Permission>  $rolePermissions  the role's permissions; [] when creating. Not `permissions`: that is the viewer's own, shared with every page
     * @param  list<ActivityData>|null  $activity  the role's timeline, newest first; null when creating or when the viewer may not read the activity log
     * @param  list<RoleHolderData>  $holders  the first users who have the role, for the dialog that blocks deleting it; `role.userCount` has the total
     */
    public function __construct(
        public ?RoleData $role,
        public array $permissionGroups,
        public array $rolePermissions,
        public ?array $activity = null,
        public array $holders = [],
    ) {}
}
