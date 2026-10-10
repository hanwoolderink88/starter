<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class DiffHunkData extends Data
{
    /**
     * @param  list<DiffLineData>  $lines  in order: context, removed and added lines
     */
    public function __construct(
        public int $oldStart,
        public int $oldLines,
        public int $newStart,
        public int $newLines,
        public array $lines,
    ) {}
}
