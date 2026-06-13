<?php

declare(strict_types=1);

namespace App\Features\Auth\Concerns;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared post-authentication redirect logic for Fortify response contracts.
 *
 * The login form is submitted as an Inertia request. Inertia follows the
 * resulting redirect over XHR and expects an Inertia response back, so when the
 * intended target is a non-Inertia page — such as Passport's OAuth consent
 * screen during the MCP authorization flow — it receives raw HTML and surfaces
 * it in an error modal. In that case we respond with `Inertia::location()` (a
 * 409 carrying `X-Inertia-Location`) so the client performs a full-page visit
 * and the browser loads the consent screen directly.
 */
trait RedirectsAfterAuthentication
{
    /**
     * @param  Request  $request
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $target = $request->session()->pull('url.intended', Fortify::redirects('login'));

        if ($request->hasHeader('X-Inertia') && $this->leavesInertiaApp($target)) {
            return Inertia::location($target);
        }

        return redirect()->to($target);
    }

    /**
     * Determine whether the redirect target is served outside the Inertia app.
     */
    private function leavesInertiaApp(string $target): bool
    {
        $path = parse_url($target, PHP_URL_PATH);

        return is_string($path) && str_starts_with($path, '/oauth/');
    }
}
