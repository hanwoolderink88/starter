<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum DiffLineType: string
{
    case Context = 'context';
    case Removed = 'removed';
    case Added = 'added';
}
