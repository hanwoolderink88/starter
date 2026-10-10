<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TextDiffData extends Data
{
    /**
     * @param  list<DiffHunkData>  $hunks  in text order
     */
    public function __construct(
        public array $hunks,
        public int $additions,
        public int $deletions,
    ) {}
}
