<?php

declare(strict_types=1);

namespace App\Features\Roles\Services;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Infrastructure\Activity\Exceptions\StaleVersionException;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\PermissionRegistrar;

/**
 * Persistence for roles and their permissions.
 *
 * Roles are always looked up without the `active` scope and created through
 * the query builder, never with Spatie's `findOrCreate`/`findByName`/
 * `Role::create`: those respect the scope, so a deactivated role would look
 * absent and creating it again would hit the unique name.
 */
class RoleService
{
    public const int TIMELINE_LIMIT = 20;

    public function __construct(
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * Create the permission rows that the enum has and the database lacks.
     *
     * @return list<string> the names of the permissions created
     */
    public function createMissingPermissions(): array
    {
        $existing = PermissionModel::query()->where('guard_name', 'web')->pluck('name')->all();
        $created = [];

        foreach (Permission::cases() as $permission) {
            if (! in_array($permission->value, $existing, true)) {
                PermissionModel::query()->create(['name' => $permission->value, 'guard_name' => 'web']);
                $created[] = $permission->value;
            }
        }

        return $created;
    }

    /**
     * The protected role, created when there is none yet. Check
     * `wasRecentlyCreated` on the result.
     */
    public function firstOrCreateProtected(string $name, string $description): Role
    {
        /** @var Role */
        return $this->unscoped()->firstOrCreate(['is_protected' => true], [
            'name' => $name,
            'guard_name' => 'web',
            'description' => $description,
            'is_active' => true,
        ]);
    }

    /**
     * The role with this name, active or not, created when there is none yet.
     * An existing role is returned untouched. Check `wasRecentlyCreated` on
     * the result.
     */
    public function firstOrCreateByName(string $name, string $description): Role
    {
        /** @var Role */
        return $this->unscoped()->firstOrCreate(['name' => $name, 'guard_name' => 'web'], [
            'description' => $description,
            'is_active' => true,
            'is_protected' => false,
        ]);
    }

    /**
     * Add these permissions to the role, keeping the ones it already has.
     *
     * @param  list<Permission>  $permissions
     */
    public function givePermissions(Role $role, array $permissions): Role
    {
        $role->givePermissionTo($permissions);

        return $role->load('permissions');
    }

    /**
     * Every role, inactive ones included, the protected one first and then by
     * name, with the number of permissions and of users.
     *
     * @return Collection<int, Role>
     */
    public function all(): Collection
    {
        return $this->unscoped()
            ->withCount(['permissions', 'users'])
            ->orderByDesc('is_protected')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<Permission>  $permissions
     */
    public function store(string $name, ?string $description, array $permissions): Role
    {
        /** @var Role $role */
        $role = Role::query()->create([
            'name' => $name,
            'guard_name' => 'web',
            'description' => $description,
            'is_active' => true,
            'is_protected' => false,
        ]);

        $role->syncPermissions($permissions);

        return $role->load('permissions');
    }

    /**
     * Save the name and description at the expected version and replace the
     * permissions. The permission cache is cleared, so the change applies at
     * once.
     *
     * @param  list<Permission>  $permissions
     *
     * @throws StaleVersionException when the role changed since `$expectedVersion`
     */
    public function update(Role $role, int $expectedVersion, string $name, ?string $description, array $permissions): Role
    {
        $role->updateAtVersion($expectedVersion, [
            'name' => $name,
            'description' => $description,
        ]);

        $role->syncPermissions($permissions);

        $this->permissionRegistrar->forgetCachedPermissions();

        return $role->load('permissions');
    }

    /**
     * Switch the role between active and inactive. Saved through the model,
     * so Spatie's cache listener runs too.
     */
    public function toggleActive(Role $role): Role
    {
        $role->is_active = ! $role->is_active;
        $role->version++;
        $role->save();

        $this->permissionRegistrar->forgetCachedPermissions();

        return $role;
    }

    /**
     * Whether any user has this role, active or not.
     */
    public function isAssigned(Role $role): bool
    {
        return $role->users()->exists();
    }

    /**
     * The first users, by name, who have this role.
     *
     * @return list<User>
     */
    public function assignedUsers(Role $role, int $limit): array
    {
        $users = [];

        foreach ($role->users()->orderBy('name')->limit($limit)->get() as $user) {
            if ($user instanceof User) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Delete the role. Its permission links go with it through the foreign
     * keys; the caller makes sure nobody has it any more.
     */
    public function delete(Role $role): void
    {
        $role->delete();

        $this->permissionRegistrar->forgetCachedPermissions();
    }

    /**
     * The role's permissions in catalogue order.
     *
     * @return list<Permission>
     */
    public function permissionsOf(Role $role): array
    {
        $names = $role->permissions()->pluck('name')->all();

        return array_values(array_filter(
            Permission::cases(),
            fn (Permission $permission): bool => in_array($permission->value, $names, true),
        ));
    }

    /**
     * The names of the role's permissions, sorted, for the activity log.
     *
     * @return list<string>
     */
    public function permissionNames(Role $role): array
    {
        /** @var list<string> $names */
        $names = $role->permissions()->orderBy('name')->pluck('name')->all();

        return $names;
    }

    /**
     * The fields of a role as the activity log records them.
     *
     * @return array{name: string, description: string|null, is_active: bool, is_protected: bool, permissions: list<string>}
     */
    public function activityAttributes(Role $role): array
    {
        return [
            'name' => $role->name,
            'description' => $role->description,
            'is_active' => $role->is_active,
            'is_protected' => $role->is_protected,
            'permissions' => $this->permissionNames($role),
        ];
    }

    /**
     * The newest log entries about the role.
     *
     * @return Collection<int, Activity>
     */
    public function timeline(Role $role): Collection
    {
        return Activity::query()
            ->forSubject($role)
            ->limit(self::TIMELINE_LIMIT)
            ->get();
    }

    /**
     * How many roles there are, and how many of them are inactive.
     *
     * @return array{total: int, inactive: int}
     */
    public function counts(): array
    {
        return [
            'total' => $this->unscoped()->count(),
            'inactive' => $this->unscoped()->where('is_active', false)->count(),
        ];
    }

    /**
     * @return Builder<Role>
     */
    private function unscoped(): Builder
    {
        return Role::query()->withoutGlobalScope(Role::ACTIVE_SCOPE);
    }
}
