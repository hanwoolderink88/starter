<?php

declare(strict_types=1);

namespace App\Features\Activity\Data;

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ActivityFiltersData extends Data
{
    public function __construct(
        public ?ActivityEvent $event = null,
        public ?ActivitySource $source = null,
        public ?int $causerId = null,
        public ?string $subjectType = null,
        public ?int $subjectId = null,
        /** Inclusive, as Y-m-d. */
        public ?string $from = null,
        /** Inclusive, as Y-m-d. */
        public ?string $to = null,
    ) {}

    /**
     * The filters from a query string or a tool call. Unknown values are
     * ignored rather than rejected, like the user list.
     *
     * @param  array<string, mixed>  $input
     * @param  list<string>  $subjectTypes  the morph aliases that may be filtered on
     */
    public static function fromInput(array $input, array $subjectTypes): self
    {
        $subjectType = $input['subject_type'] ?? null;

        return new self(
            event: is_string($input['event'] ?? null) ? ActivityEvent::tryFrom($input['event']) : null,
            source: is_string($input['source'] ?? null) ? ActivitySource::tryFrom($input['source']) : null,
            causerId: self::positiveInteger($input['causer_id'] ?? null),
            subjectType: is_string($subjectType) && in_array($subjectType, $subjectTypes, true) ? $subjectType : null,
            subjectId: self::positiveInteger($input['subject_id'] ?? null),
            from: self::date($input['from'] ?? null),
            to: self::date($input['to'] ?? null),
        );
    }

    private static function positiveInteger(mixed $value): ?int
    {
        $integer = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $integer === false ? null : $integer;
    }

    private static function date(mixed $value): ?string
    {
        return is_string($value)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1
            && checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))
            ? $value
            : null;
    }
}
