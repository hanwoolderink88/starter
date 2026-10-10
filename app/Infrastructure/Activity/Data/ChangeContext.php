<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Data;

use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Mcp\Request as McpRequest;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\Hidden;

/**
 * Who makes a change, through which surface, and from where.
 *
 * Every Action that mutates data receives one context instead of loose actor
 * and origin parameters. The activity log and the co-working signal both read
 * from it, so a change is always traceable to a person, a source and, when
 * someone is impersonated, the real user behind it. Backend-only, so it is
 * hidden from the generated TypeScript types.
 */
#[Hidden]
class ChangeContext extends Data
{
    /**
     * The session key that marks an impersonation: who impersonates whom.
     */
    public const string IMPERSONATION_SESSION_KEY = 'impersonation';

    public function __construct(
        public ?User $actor,
        public ActivitySource $source,
        /**
         * The initiating web tab's client id, used to suppress that tab's own
         * echo. Null for every non-web surface. Never stored in the log.
         */
        public ?string $origin = null,
        public ?User $impersonator = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}

    /**
     * A change made in the browser. The actor defaults to the authenticated
     * user; pass one explicitly for a guest flow such as accepting an
     * invitation or resetting a password, where the user acts before being
     * logged in. While the session impersonates someone, the real user is the
     * impersonator.
     */
    public static function fromRequest(Request $request, ?User $actor = null): self
    {
        $user = $actor ?? $request->user();

        return new self(
            actor: $user instanceof User ? $user : null,
            source: ActivitySource::Web,
            origin: $request->header('X-Client-Id'),
            impersonator: self::impersonatorFor($request),
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        );
    }

    /**
     * The real user behind this session when it impersonates someone. The
     * session remembers both users, so a stale marker (the impersonated user
     * logged out, or someone else logged in) never names an impersonator.
     */
    public static function impersonatorFor(Request $request): ?User
    {
        if (! $request->hasSession()) {
            return null;
        }

        /** @var array{impersonator_id?: int, impersonated_id?: int}|null $impersonation */
        $impersonation = $request->session()->get(self::IMPERSONATION_SESSION_KEY);
        $user = $request->user();

        if (! is_array($impersonation) || ! $user instanceof User || ($impersonation['impersonated_id'] ?? null) !== $user->id) {
            return null;
        }

        return User::find($impersonation['impersonator_id'] ?? null);
    }

    /**
     * A change made by Claude through an MCP tool, on behalf of the token's
     * user. MCP has no impersonation: a token always belongs to its own user.
     * The MCP request carries no network details, so those come from the HTTP
     * request it arrived on.
     */
    public static function fromMcpRequest(McpRequest $request): self
    {
        $user = $request->user();
        $httpRequest = request();

        return new self(
            actor: $user instanceof User ? $user : null,
            source: ActivitySource::Mcp,
            ipAddress: $httpRequest->ip(),
            userAgent: $httpRequest->userAgent(),
        );
    }

    /**
     * A change made on behalf of a given user, outside that user's own
     * request: closing someone's expired editing session names its holder,
     * with the source and impersonator of that session, not whoever caused
     * the closing. No browser request, so no network details.
     */
    public static function forActor(User $actor, ActivitySource $source, ?User $impersonator = null): self
    {
        return new self(
            actor: $actor,
            source: $source,
            impersonator: $impersonator,
        );
    }

    /**
     * A change made by the application itself: a seeder, a command or a job.
     */
    public static function system(): self
    {
        return new self(
            actor: null,
            source: ActivitySource::System,
        );
    }

    /**
     * The name to show and to snapshot for whoever made the change: the
     * application itself, or a guest such as someone failing to log in.
     */
    public function causerName(): string
    {
        if ($this->actor !== null) {
            return $this->actor->name;
        }

        return $this->source === ActivitySource::System ? __('activity.system') : __('activity.guest');
    }
}
