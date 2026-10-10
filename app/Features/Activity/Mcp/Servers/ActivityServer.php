<?php

declare(strict_types=1);

namespace App\Features\Activity\Mcp\Servers;

use App\Features\Activity\Mcp\Tools\SearchActivityTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Override;

#[Name('Activity')]
#[Version('1.0.0')]
#[Instructions('Search the activity log to find out who did what and when.')]
class ActivityServer extends Server
{
    /**
     * @var list<class-string<Tool>>
     */
    public const TOOLS = [
        SearchActivityTool::class,
    ];

    /**
     * @var array<int|string, Tool|class-string<Tool>|array<int, Tool|class-string<Tool>>>
     */
    #[Override]
    protected array $tools = self::TOOLS;
}
