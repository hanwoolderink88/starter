# Roles & Permissions Rules

## Core Principle

Permissions live in code, roles live in the database. A permission without code does nothing, so the catalogue is an enum; which role has which permission, and who has which role, is configuration that administrators manage on `/roles` and in the user form.

## Where Things Live

- `app/Features/Roles/` owns roles: the `Role` model (extends Spatie's, registered in `config/permission.php`), the permission catalogue `Enums/Permission` with its `Enums/PermissionGroup`, the role screens, `RolePolicy`, and the Actions that change roles and assignments.
- Other features use only the public surface: the `Permission` enum (in policies), the Actions `SyncUserRolesAction` / `RevokeUserRoleAction`, and the contracts `ListsAssignableRoles` and `GuardsLastAdministrator`. Never query `Role` or `model_has_roles` from another feature.

## Adding a Permission

1. Add a case to `App\Features\Roles\Enums\Permission` and map it in `group()` (add a `PermissionGroup` case when no group fits).
2. Add its label under `roles.permissions.{value}` (and a new group under `roles.groups.{value}`) in `lang/en/roles.php`.
3. Check it in the feature's policy: `$user->can(Permission::X->value)`. Never check a role name.
4. Run `php artisan permissions:sync` (also on every deploy): it creates the permission and gives it to the Administrator role. Other roles get it when an administrator switches it on.

## The Administrator Role

Exactly one role is protected (`is_protected`, enforced by a partial unique index). It always has every permission, can be renamed but never deactivated or deleted, and there is always at least one user who accepted their invitation and has it: deleting that user, their own account deletion, and taking the role away all throw `LastAdministratorException`. There is no `Gate::before` bypass: the administrator works through its permissions like any other role.

## Active and Inactive Roles

The `Role` global scope `active` hides inactive roles from `$user->roles`, `findByName`/`findById` and Spatie's permission cache, so an inactive role grants nothing while its assignments stay in `model_has_roles`.

- Look up roles that may be inactive with `withoutGlobalScope(Role::ACTIVE_SCOPE)`; route binding already does.
- Never use Spatie's `syncRoles`/`assignRole` from a feature: `SyncUserRolesAction` keeps inactive assignments and refuses inactive roles. An inactive assignment is only removed by `RevokeUserRoleAction`.
- Validate role ids with the `ActiveRole` rule.

## Who May Assign

Giving or taking roles needs `manage roles` (`UserPolicy::assignRoles`), the same permission as managing the roles themselves, so `create users` or `update users` alone can never hand out permissions. Posting the set a user already has is not an assignment. Left out (`role_ids` absent) means unchanged; present, also empty, means exactly that set.

## Logging

Every change to a role (created, updated with old and new permissions, status changed, deleted with a snapshot) and every change to a user's roles (`roles_changed`, old and new role names) is logged from the Action. The seeder and `permissions:sync` log with source `system`, and only when something changed.
