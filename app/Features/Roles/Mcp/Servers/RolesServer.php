<?php

declare(strict_types=1);

namespace App\Features\Roles\Mcp\Servers;

use App\Features\Roles\Mcp\Tools\ListRolesTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Override;

#[Name('Roles')]
#[Version('1.0.0')]
#[Instructions('Lists the roles that can be given to users. Roles themselves are managed in the web interface only.')]
class RolesServer extends Server
{
    /**
     * @var list<class-string<Tool>>
     */
    public const TOOLS = [
        ListRolesTool::class,
    ];

    /**
     * @var array<int|string, Tool|class-string<Tool>|array<int, Tool|class-string<Tool>>>
     */
    #[Override]
    protected array $tools = self::TOOLS;
}
