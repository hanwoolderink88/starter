<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The surface a change came through: a browser, Claude via MCP, or the
 * application itself (seeders, commands, jobs).
 */
#[TypeScript]
enum ActivitySource: string
{
    case Web = 'web';
    case Mcp = 'mcp';
    case System = 'system';
}
