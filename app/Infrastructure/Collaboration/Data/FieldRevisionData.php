<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Data;

use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Collaboration\Models\FieldRevision;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FieldRevisionData extends Data
{
    public function __construct(
        public int $number,
        public string $authorName,
        public ?string $impersonatorName,
        public ActivitySource $source,
        public ?int $restoredFromNumber,
        public string $createdAt,
        public string $createdAtDisplay,
    ) {}

    public static function fromRevision(FieldRevision $revision): self
    {
        return new self(
            number: $revision->number,
            authorName: $revision->author_name,
            impersonatorName: $revision->impersonator_name,
            source: $revision->source,
            restoredFromNumber: $revision->restored_from_number,
            createdAt: $revision->created_at->toISOString() ?? '',
            createdAtDisplay: $revision->created_at->isoFormat('lll'),
        );
    }
}
