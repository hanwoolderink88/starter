<?php

declare(strict_types=1);

namespace App\Features\Roles\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The subject a permission is grouped under in the permission matrix. Labels
 * live under `roles.groups.{value}`.
 */
#[TypeScript]
enum PermissionGroup: string
{
    case Users = 'users';
    case Roles = 'roles';
    case Activity = 'activity';
}
