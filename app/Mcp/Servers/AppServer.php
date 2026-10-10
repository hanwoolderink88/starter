<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Features\Activity\Mcp\Servers\ActivityServer;
use App\Features\Roles\Mcp\Servers\RolesServer;
use App\Features\UserManagement\Mcp\Servers\UserManagementServer;
use Laravel\Mcp\Server;
use Override;

/**
 * Composition root for the application's single MCP endpoint.
 *
 * Each feature owns and declares its own MCP primitives; this server merges
 * them so the application exposes one HTTP server (and one OAuth flow) to
 * clients. Add a feature by spreading its public tool list in boot().
 *
 * The server name and instructions are set from the application name so the
 * connecting client (and, where it forwards them, the model) knows which
 * application it is operating on.
 */
class AppServer extends Server
{
    #[Override]
    protected function boot(): void
    {
        $appName = (string) config('app.name');

        $this->name = $appName.' MCP';
        $this->version = '1.0.0';
        $this->instructions = sprintf(
            'Administrative tools for the %s application. The tools you can see are the '
            .'ones the authenticated account is permitted to use. When creating a record, '
            .'first search to avoid creating a duplicate. Updates need the record\'s current '
            .'version; when one is refused because someone else saved first, fetch the record '
            .'again and retry. Everything you do is logged with you as the actor and source mcp; '
            .'use search_activity to find out who did what.',
            $appName,
        );

        $this->tools = [
            ...UserManagementServer::TOOLS,
            ...RolesServer::TOOLS,
            ...ActivityServer::TOOLS,
        ];
    }
}
