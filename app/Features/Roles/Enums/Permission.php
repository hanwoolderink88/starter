<?php

declare(strict_types=1);

namespace App\Features\Roles\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The permission catalogue: the public API of Roles. Policies in every feature
 * check these cases; a permission without code does nothing, so the catalogue
 * lives in code while roles live in the database. A feature that needs a new
 * permission adds a case here, its group, and its label under
 * `roles.permissions.{value}`, then runs `permissions:sync`.
 */
#[TypeScript]
enum Permission: string
{
    case ViewUsers = 'view users';
    case CreateUsers = 'create users';
    case UpdateUsers = 'update users';
    case DeleteUsers = 'delete users';
    case ImpersonateUsers = 'impersonate users';
    case ManageRoles = 'manage roles';
    case ViewActivity = 'view activity';

    public function group(): PermissionGroup
    {
        return match ($this) {
            self::ViewUsers, self::CreateUsers, self::UpdateUsers, self::DeleteUsers, self::ImpersonateUsers => PermissionGroup::Users,
            self::ManageRoles => PermissionGroup::Roles,
            self::ViewActivity => PermissionGroup::Activity,
        };
    }
}
