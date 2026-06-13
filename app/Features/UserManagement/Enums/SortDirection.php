<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum SortDirection: string
{
    case Asc = 'asc';
    case Desc = 'desc';

    public function opposite(): self
    {
        return $this === self::Asc ? self::Desc : self::Asc;
    }
}
