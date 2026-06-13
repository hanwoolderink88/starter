<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Mcp\Servers;

use App\Features\UserManagement\Mcp\Tools\CreateUserTool;
use App\Features\UserManagement\Mcp\Tools\SearchUsersTool;
use App\Features\UserManagement\Mcp\Tools\ShowUserTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Override;

#[Name('User Management')]
#[Version('1.0.0')]
#[Instructions('Provides tools to search, inspect, and create users. Tool availability and execution depend on the authenticated user\'s permissions; a user without the relevant permission will not see the tool listed.')]
class UserManagementServer extends Server
{
    /**
     * This feature's MCP tools, exposed publicly so a composition-root server
     * (e.g. AppServer) can merge them into a single endpoint.
     *
     * @var list<class-string<Tool>>
     */
    public const TOOLS = [
        SearchUsersTool::class,
        ShowUserTool::class,
        CreateUserTool::class,
    ];

    /**
     * @var array<int, class-string<Tool>|Tool>
     */
    #[Override]
    protected array $tools = self::TOOLS;
}
