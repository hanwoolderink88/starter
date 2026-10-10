<?php

declare(strict_types=1);

namespace App\Infrastructure\Broadcasting\Data;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * The thin "something changed" signal broadcast for co-working updates.
 *
 * Deliberately carries no record fields — only enough to phrase a toast,
 * decide what to reload, and suppress the originating surface's own echo. The
 * authoritative data is always re-fetched through Inertia, never read from this
 * payload.
 */
#[TypeScript]
class ResourceChangedData extends Data
{
    public function __construct(
        public ResourceAction $action,
        public int $id,
        public string $label,
        /**
         * Null when the application itself made the change (source `system`).
         */
        public ?int $actorId,
        /**
         * The bare name of whoever made the change; the frontend turns it into
         * one label together with the source and the impersonator.
         */
        public string $actorName,
        public ActivitySource $source,
        /**
         * The id of the surface that initiated the change — the web SPA tab's
         * client id, echoed back so that one tab suppresses its own echo. Null
         * when the change originates from a non-web surface (MCP or system), so
         * every browser is notified and reloads. Never use it to detect MCP:
         * `source` says where a change came from.
         */
        public ?string $origin = null,
        public ?string $impersonatorName = null,
    ) {}

    /**
     * The signal for a change, with the actor, source, origin and impersonator
     * taken from the context the Action received, so the signal and the
     * activity log name the same person.
     */
    public static function fromContext(ChangeContext $context, ResourceAction $action, int $id, string $label): self
    {
        return new self(
            action: $action,
            id: $id,
            label: $label,
            actorId: $context->actor?->id,
            actorName: $context->causerName(),
            source: $context->source,
            origin: $context->origin,
            impersonatorName: $context->impersonator?->name,
        );
    }
}
