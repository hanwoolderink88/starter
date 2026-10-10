<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Speaks the viewer's language: their own choice when they made one, else
 * their browser's preference among the supported languages, else the
 * application default.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->localeFor($request));

        return $next($request);
    }

    private function localeFor(Request $request): string
    {
        /** @var list<string> $supported */
        $supported = config('app.supported_locales');
        $user = $request->user();

        if ($user instanceof User && in_array($user->locale, $supported, true)) {
            return $user->locale;
        }

        $default = config('app.locale');
        assert(is_string($default));

        return $request->getPreferredLanguage($supported) ?? $default;
    }
}
