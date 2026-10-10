<?php

use App\Features\Activity\Commands\PruneActivityCommand;
use App\Features\Roles\Commands\SyncPermissionsCommand;
use App\Features\UserManagement\Commands\AnonymizeDeletedUsersCommand;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RestrictImpersonation;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Session\Middleware\AuthenticateSession;
use Inertia\Inertia;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withCommands([
        SyncPermissionsCommand::class,
        AnonymizeDeletedUsersCommand::class,
        PruneActivityCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            AuthenticateSession::class,
            HandleAppearance::class,
            SetLocale::class,
            RestrictImpersonation::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (InvalidSignatureException $e, Request $request) {
            if ($request->expectsJson() || $request->is('invitation/*')) {
                return Inertia::render('auth/invitation-expired')->toResponse($request)->setStatusCode(403);
            }

            return response('Link expired or invalid', 403);
        });
    })->create();
