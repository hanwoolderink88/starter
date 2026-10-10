<?php

declare(strict_types=1);

namespace App\Features\Settings\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class SessionsPageData extends Data
{
    /**
     * @param  list<SessionData>  $sessions  the viewer's sessions, most recently active first
     * @param  bool  $available  whether sessions are kept in the database, so they can be listed
     */
    public function __construct(
        public array $sessions,
        public bool $available,
    ) {}
}
