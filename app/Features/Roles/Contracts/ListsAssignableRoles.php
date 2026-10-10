<?php

declare(strict_types=1);

namespace App\Features\Roles\Contracts;

use App\Features\Roles\Data\AssignableRoleData;
use App\Features\Roles\Services\RoleAssignmentService;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;

/**
 * The roles a user can be given, and the ones a user has, for features that
 * offer roles in a form or a filter and must not reach into the Roles models.
 */
#[Bind(RoleAssignmentService::class)]
interface ListsAssignableRoles
{
    /**
     * Every role, active or not, the protected one first and then by name.
     *
     * @return list<AssignableRoleData>
     */
    public function assignable(): array;

    /**
     * The ids of all the user's roles, inactive assignments included, sorted.
     *
     * @return list<int>
     */
    public function roleIdsOf(User $user): array;
}
