<?php

declare(strict_types=1);

use App\Mcp\Servers\AppServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::oauthRoutes();

Mcp::web('/mcp', AppServer::class)
    ->middleware('auth:api');
