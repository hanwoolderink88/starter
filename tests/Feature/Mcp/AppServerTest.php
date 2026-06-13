<?php

use App\Features\UserManagement\Mcp\Tools\SearchUsersTool;
use App\Mcp\Servers\AppServer;
use App\Models\User;
use Laravel\Mcp\Server\Transport\FakeTransporter;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('app server identity and instructions are derived from the application name', function () {
    $appName = (string) config('app.name');

    $server = new AppServer(new FakeTransporter);
    $server->start();
    $context = $server->createContext();

    expect($context->implementation->name)->toBe($appName.' MCP')
        ->and($context->instructions)->toContain($appName);
});

test('app server exposes merged feature tools to authorized users', function () {
    $admin = createAdmin();
    User::factory()->create(['email' => 'via-app-server@example.com']);

    AppServer::actingAs($admin)
        ->tool(SearchUsersTool::class, ['search' => 'via-app-server'])
        ->assertOk()
        ->assertSee('via-app-server@example.com');
});

test('app server preserves per-tool permission gating', function () {
    AppServer::actingAs(createRegularUser())
        ->tool(SearchUsersTool::class, ['search' => 'anything'])
        ->assertHasErrors(['Tool [search_user] not found.']);
});
