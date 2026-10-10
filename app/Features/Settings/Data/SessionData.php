<?php

declare(strict_types=1);

namespace App\Features\Settings\Data;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One browser session of the viewer. The session id itself never leaves the
 * server: knowing it is enough to take the session over.
 */
#[TypeScript]
class SessionData extends Data
{
    public function __construct(
        public ?string $ipAddress,
        /** The browser, such as "Firefox", or null when it cannot be told. */
        public ?string $browser,
        /** The operating system, such as "macOS", or null when it cannot be told. */
        public ?string $platform,
        public bool $isDesktop,
        public bool $isCurrentDevice,
        public string $lastActiveAt,
        /** "2 minutes ago", in the viewer's language. */
        public string $lastActiveDisplay,
    ) {}

    /**
     * @param  object{id: string, ip_address: string|null, user_agent: string|null, last_activity: int|string}  $row  a row of the sessions table
     */
    public static function fromRow(object $row, string $currentSessionId): self
    {
        $agent = $row->user_agent ?? '';
        $lastActive = CarbonImmutable::createFromTimestamp((int) $row->last_activity);

        return new self(
            ipAddress: $row->ip_address,
            browser: self::match($agent, [
                'Edge' => '/Edg\//',
                'Opera' => '/OPR\//',
                'Chrome' => '/Chrome\//',
                'Firefox' => '/Firefox\//',
                'Safari' => '/Safari\//',
            ]),
            platform: self::match($agent, [
                'iOS' => '/iPhone|iPad/',
                'Android' => '/Android/',
                'Windows' => '/Windows/',
                'macOS' => '/Macintosh|Mac OS X/',
                'Linux' => '/Linux/',
            ]),
            isDesktop: preg_match('/Mobile|Android|iPhone|iPad/', $agent) !== 1,
            isCurrentDevice: $row->id === $currentSessionId,
            lastActiveAt: $lastActive->toISOString() ?? '',
            // Carbon follows the application locale, which SetLocale sets.
            lastActiveDisplay: $lastActive->diffForHumans(),
        );
    }

    /**
     * The first name whose pattern matches the user agent.
     *
     * @param  array<string, string>  $patterns
     */
    private static function match(string $agent, array $patterns): ?string
    {
        foreach ($patterns as $name => $pattern) {
            if (preg_match($pattern, $agent) === 1) {
                return $name;
            }
        }

        return null;
    }
}
