<?php

declare(strict_types=1);

namespace App\Features\Activity\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Someone who appears in the log, as an option for the "performed by" filter.
 */
#[TypeScript]
class ActivityCauserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
