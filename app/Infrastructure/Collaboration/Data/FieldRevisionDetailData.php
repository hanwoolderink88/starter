<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One revision with its text, and what changed from it to the current text.
 */
#[TypeScript]
class FieldRevisionDetailData extends Data
{
    public function __construct(
        public FieldRevisionData $revision,
        public string $content,
        /** From this revision to the current value. */
        public TextDiffData $diffToCurrent,
    ) {}
}
