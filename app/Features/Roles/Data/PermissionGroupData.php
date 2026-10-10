<?php

declare(strict_types=1);

namespace App\Features\Roles\Data;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Enums\PermissionGroup;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One group of the permission matrix, in the order of the catalogue.
 */
#[TypeScript]
class PermissionGroupData extends Data
{
    /**
     * @param  list<Permission>  $permissions
     */
    public function __construct(
        public PermissionGroup $group,
        public array $permissions,
    ) {}

    /**
     * Every group of the catalogue with its permissions.
     *
     * @return list<self>
     */
    public static function catalogue(): array
    {
        $groups = [];

        foreach (Permission::cases() as $permission) {
            $group = $permission->group();
            $groups[$group->value] ??= new self(group: $group, permissions: []);
            $groups[$group->value]->permissions[] = $permission;
        }

        return array_values($groups);
    }
}
