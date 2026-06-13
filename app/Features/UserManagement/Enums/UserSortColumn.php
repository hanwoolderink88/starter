<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum UserSortColumn: string
{
    case Name = 'name';
    case Email = 'email';
    case CreatedAt = 'created_at';
}
