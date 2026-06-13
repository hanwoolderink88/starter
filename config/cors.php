<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Browser-based MCP clients (e.g. the MCP Inspector, and web clients such
    | as claude.ai) run the OAuth 2.1 authorization-code + PKCE token exchange
    | directly from the browser. Those cross-origin fetches to the OAuth and
    | MCP endpoints require CORS headers, so we expose the relevant paths here.
    |
    | Native clients (Claude Desktop) use their own HTTP client and are not
    | subject to CORS, so they do not depend on this configuration.
    |
    */

    'paths' => [
        'mcp/*',
        'oauth/*',
        '.well-known/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:6274', // MCP Inspector
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
