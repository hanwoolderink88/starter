<?php

declare(strict_types=1);

namespace App\Http\Inertia;

use Illuminate\Support\Str;
use Inertia\Ssr\HttpGateway;
use Override;

/**
 * SSR gateway that allows overriding the Vite dev-server URL used for SSR.
 *
 * In local development the app runs inside the Sail container while the Vite
 * dev server runs on the host. The hot file contains a browser-facing URL
 * (localhost), which the container cannot reach. INERTIA_SSR_HOT_URL points
 * the container at the host instead (host.docker.internal).
 */
class SailSsrGateway extends HttpGateway
{
    #[Override]
    protected function getHotUrl(string $path = '/'): string
    {
        $hotUrl = config('inertia.ssr.hot_url');

        if (is_string($hotUrl) && $hotUrl !== '') {
            return rtrim($hotUrl, '/').Str::start($path, '/');
        }

        return parent::getHotUrl($path);
    }
}
