<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Data;

use App\Infrastructure\Collaboration\Enums\DiffLineType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class DiffLineData extends Data
{
    public function __construct(
        public DiffLineType $type,
        public string $content,
        public ?int $oldNumber,
        public ?int $newNumber,
    ) {}
}
