<?php

declare(strict_types=1);

namespace App\Features\Settings\Controllers;

use App\Features\Settings\Data\TwoFactorPageData;
use App\Features\Settings\Requests\TwoFactorAuthenticationRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TwoFactorAuthenticationController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        $middleware = [
            new Middleware(self::ensureTwoFactorEnabled(...)),
        ];

        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            $middleware[] = new Middleware('password.confirm', only: ['show']);
        }

        return $middleware;
    }

    /**
     * Show the user's two-factor authentication settings page.
     */
    public function show(TwoFactorAuthenticationRequest $request): Response
    {
        $request->ensureStateIsValid();

        $user = $request->user();
        assert($user instanceof User);

        return Inertia::render('settings/two-factor', new TwoFactorPageData(
            twoFactorEnabled: $user->hasEnabledTwoFactorAuthentication(),
            requiresConfirmation: Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
        ));
    }

    /**
     * Ensure the two-factor authentication feature is enabled.
     */
    private static function ensureTwoFactorEnabled(Request $request, Closure $next): mixed
    {
        if (! Features::enabled(Features::twoFactorAuthentication())) {
            throw new AccessDeniedHttpException;
        }

        return $next($request);
    }
}
