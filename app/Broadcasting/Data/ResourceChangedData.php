<?php

declare(strict_types=1);

namespace App\Broadcasting\Data;

use App\Broadcasting\Enums\ResourceAction;
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
        public int $actorId,
        public string $actorName,
        /**
         * The id of the surface that initiated the change — the web SPA tab's
         * client id, echoed back so that one tab suppresses its own echo. Null
         * when the change originates from a non-web surface (e.g. MCP), so every
         * browser is notified and reloads.
         */
        public ?string $origin = null,
    ) {}
}
