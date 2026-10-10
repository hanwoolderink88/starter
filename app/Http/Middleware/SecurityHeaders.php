<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hardens every response: no framing, no MIME sniffing, a strict referrer and
 * permissions policy, HSTS over HTTPS, and a Content Security Policy that
 * only runs scripts from this origin carrying this request's nonce.
 */
class SecurityHeaders
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure() && (bool) config('security.hsts.enabled')) {
            $headers->set('Strict-Transport-Security', 'max-age='.config('security.hsts.max_age').'; includeSubDomains');
        }

        if ((bool) config('security.csp.enabled')) {
            $headers->set(
                (bool) config('security.csp.report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy',
                $this->contentSecurityPolicy($nonce),
            );
        }

        return $response;
    }

    private function contentSecurityPolicy(string $nonce): string
    {
        $dev = $this->viteDevServer();
        $fonts = 'https://fonts.bunny.net';

        /** @var list<string> $extraConnect */
        $extraConnect = config('security.csp.connect_src');
        $reverb = config('security.csp.reverb');

        $directives = [
            'default-src' => ["'self'"],
            'script-src' => ["'self'", "'nonce-{$nonce}'", ...$dev],
            // React, Radix and Inertia set inline styles at runtime.
            'style-src' => ["'self'", "'unsafe-inline'", $fonts, ...$dev],
            'img-src' => ["'self'", 'data:', 'blob:', ...$dev],
            'font-src' => ["'self'", 'data:', $fonts, ...$dev],
            'connect-src' => [
                "'self'",
                ...(is_string($reverb) ? [$reverb] : []),
                ...$dev,
                ...array_map(fn (string $origin): string => (string) preg_replace('/^http/', 'ws', $origin), $dev),
                ...$extraConnect,
            ],
            'frame-ancestors' => ["'none'"],
            'base-uri' => ["'self'"],
            'object-src' => ["'none'"],
        ];

        return implode('; ', array_map(
            fn (string $directive, array $sources): string => $directive.' '.implode(' ', array_unique($sources)),
            array_keys($directives),
            $directives,
        ));
    }

    /**
     * The origin of the Vite dev server while `npm run dev` runs, else none.
     *
     * @return list<string>
     */
    private function viteDevServer(): array
    {
        if (! Vite::isRunningHot()) {
            return [];
        }

        $url = trim(File::get(Vite::hotFile()));

        return $url === '' ? [] : [$url];
    }
}
