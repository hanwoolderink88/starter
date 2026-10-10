<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Concerns;

use App\Features\Roles\Contracts\ListsAssignableRoles;
use App\Features\Roles\Data\AssignableRoleData;

/**
 * An agent names roles, the forms send ids. Only active roles can be given.
 */
trait ResolvesRoleNames
{
    /**
     * @return list<string>
     */
    protected function activeRoleNames(ListsAssignableRoles $assignableRoles): array
    {
        return array_map(fn (AssignableRoleData $role): string => $role->name, $this->activeRoles($assignableRoles));
    }

    /**
     * @param  list<string>  $names  validated names of active roles
     * @return list<int>
     */
    protected function roleIdsFor(ListsAssignableRoles $assignableRoles, array $names): array
    {
        return array_values(array_map(
            fn (AssignableRoleData $role): int => $role->id,
            array_filter($this->activeRoles($assignableRoles), fn (AssignableRoleData $role): bool => in_array($role->name, $names, true)),
        ));
    }

    /**
     * @return list<AssignableRoleData>
     */
    private function activeRoles(ListsAssignableRoles $assignableRoles): array
    {
        return array_values(array_filter($assignableRoles->assignable(), fn (AssignableRoleData $role): bool => $role->isActive));
    }
}
