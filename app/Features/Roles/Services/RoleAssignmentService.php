<?php

declare(strict_types=1);

namespace App\Features\Roles\Services;

use App\Features\Roles\Contracts\GuardsLastAdministrator;
use App\Features\Roles\Contracts\ListsAssignableRoles;
use App\Features\Roles\Data\AssignableRoleData;
use App\Features\Roles\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\PermissionRegistrar;

/**
 * Which roles a user has and can be given, and the persistence of those
 * assignments in `model_has_roles`.
 *
 * The pivot is always read without the `active` scope: rows of inactive roles
 * are never detached by a sync, and an inactive role that is already there is
 * never attached again. Spatie's `syncRoles` is not used because it detaches
 * the whole pivot, inactive assignments included.
 */
class RoleAssignmentService implements GuardsLastAdministrator, ListsAssignableRoles
{
    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    public function assignable(): array
    {
        return array_values(Role::query()
            ->withoutGlobalScope(Role::ACTIVE_SCOPE)
            ->orderByDesc('is_protected')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): AssignableRoleData => AssignableRoleData::fromRole($role))
            ->all());
    }

    public function roleIdsOf(User $user): array
    {
        return $this->allIds($user->roles());
    }

    /**
     * The ids of the user's active roles, sorted.
     *
     * @return list<int>
     */
    public function activeRoleIdsOf(User $user): array
    {
        /** @var list<int> $ids */
        $ids = $user->roles()->orderBy($this->roleKey())->pluck($this->roleKey())->all();

        return $ids;
    }

    /**
     * The ids among these that are active roles, sorted. Inactive and unknown
     * ids are dropped.
     *
     * @param  list<int>  $roleIds
     * @return list<int>
     */
    public function activeAmong(array $roleIds): array
    {
        /** @var list<int> $ids */
        $ids = Role::query()->whereKey($roleIds)->orderBy($this->roleKey())->pluck($this->roleKey())->all();

        return $ids;
    }

    /**
     * The names of all the user's roles, inactive ones included, sorted, as
     * the activity log records them.
     *
     * @return list<string>
     */
    public function roleNamesOf(User $user): array
    {
        /** @var list<string> $names */
        $names = $user->roles()
            ->withoutGlobalScope(Role::ACTIVE_SCOPE)
            ->orderBy('name')
            ->pluck('name')
            ->all();

        return $names;
    }

    /**
     * The id of the protected administrator role.
     */
    public function administratorRoleId(): ?int
    {
        /** @var int|null $id */
        $id = Role::query()->withoutGlobalScope(Role::ACTIVE_SCOPE)->where('is_protected', true)->value('id');

        return $id;
    }

    public function isLastAdministrator(User $user): bool
    {
        if (! $user->roles()->where('is_protected', true)->exists()) {
            return false;
        }

        return ! User::query()
            ->whereKeyNot($user->getKey())
            ->whereNotNull('password')
            ->whereHas('roles', function (Builder $query): void {
                $query->where('is_protected', true);
            })
            ->exists();
    }

    /**
     * Replace the user's active roles with these ids, keeping the assignments
     * of inactive roles. Ids of roles that are not active are ignored.
     *
     * @param  list<int>  $roleIds
     */
    public function syncActiveRoles(User $user, array $roleIds): void
    {
        $relation = $user->roles();
        $assigned = $this->allIds($relation);
        $requested = $this->activeAmong($roleIds);

        $relation->detach(array_values(array_diff($this->activeRoleIdsOf($user), $requested)));
        $relation->attach(array_values(array_diff($requested, $assigned)));

        $this->forget($user);
    }

    /**
     * Remove one role from the user, active or not.
     */
    public function revokeRole(User $user, int $roleId): void
    {
        $user->roles()->detach($roleId);

        $this->forget($user);
    }

    /**
     * Whether the user has this role, active or not.
     */
    public function hasRole(User $user, int $roleId): bool
    {
        return in_array($roleId, $this->roleIdsOf($user), true);
    }

    private function forget(User $user): void
    {
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        $this->permissionRegistrar->forgetCachedPermissions();
    }

    /**
     * @param  BelongsToMany<Role, User>  $relation
     * @return list<int>
     */
    private function allIds(BelongsToMany $relation): array
    {
        /** @var list<int> $ids */
        $ids = (clone $relation)
            ->withoutGlobalScope(Role::ACTIVE_SCOPE)
            ->orderBy($this->roleKey())
            ->pluck($this->roleKey())
            ->all();

        return $ids;
    }

    private function roleKey(): string
    {
        return Role::query()->getModel()->getQualifiedKeyName();
    }
}
