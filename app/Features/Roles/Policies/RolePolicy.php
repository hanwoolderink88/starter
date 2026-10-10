<?php

declare(strict_types=1);

namespace App\Features\Roles\Policies;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Models\User;

/**
 * Managing roles needs `manage roles`. The protected administrator role can be
 * renamed and described, but never deactivated or deleted: that is a policy
 * rule, so the controllers refuse it with a 403 and the Actions stay free of
 * authorization.
 */
class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ManageRoles->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(Permission::ManageRoles->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::ManageRoles->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can(Permission::ManageRoles->value);
    }

    public function toggleActive(User $user, Role $role): bool
    {
        return ! $role->is_protected && $user->can(Permission::ManageRoles->value);
    }

    public function delete(User $user, Role $role): bool
    {
        return ! $role->is_protected && $user->can(Permission::ManageRoles->value);
    }
}
