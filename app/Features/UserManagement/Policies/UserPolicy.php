<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Policies;

use App\Features\Roles\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewUsers->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(Permission::ViewUsers->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateUsers->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(Permission::UpdateUsers->value);
    }

    /**
     * Giving or taking roles needs `manage roles`, the permission for managing
     * the roles themselves: with only `create users` or `update users` nobody
     * can hand out permissions, not even to themselves. Posting the set the
     * user already has is no assignment, so a user can be renamed without it.
     *
     * @param  list<int>|null  $roleIds  the requested active roles
     */
    public function assignRoles(User $user, ?User $model = null, ?array $roleIds = null): bool
    {
        if ($roleIds !== null && $this->isCurrentRoleSet($model, $roleIds)) {
            return true;
        }

        return $user->can(Permission::ManageRoles->value);
    }

    /**
     * Bringing back a deleted user who is not anonymized yet.
     */
    public function restore(User $user, User $model): bool
    {
        return $model->trashed()
            && $model->anonymized_at === null
            && $user->can(Permission::DeleteUsers->value);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can(Permission::DeleteUsers->value);
    }

    /**
     * Nobody impersonates themselves or someone who may impersonate too: an
     * administrator's account is never borrowed, not even by another
     * administrator.
     */
    public function impersonate(User $user, User $model): bool
    {
        if ($user->id === $model->id || $model->can(Permission::ImpersonateUsers->value)) {
            return false;
        }

        return $user->can(Permission::ImpersonateUsers->value);
    }

    /**
     * @param  list<int>  $roleIds
     */
    private function isCurrentRoleSet(?User $model, array $roleIds): bool
    {
        $current = $model instanceof User
            ? $model->roles()->pluck($model->roles()->getRelated()->getQualifiedKeyName())->map(fn (mixed $id): int => (int) $id)->sort()->values()->all()
            : [];

        $requested = array_values(array_unique($roleIds));
        sort($requested);

        return $requested === $current;
    }
}
