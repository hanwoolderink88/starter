<?php

declare(strict_types=1);

namespace App\Features\Activity\Data;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ActivityPageData extends Data
{
    /**
     * @param  PaginatedDataCollection<int, ActivityLogEntryData>  $entries
     * @param  list<ActivityCauserData>  $causerOptions  everyone who appears in the log, by name
     * @param  list<string>  $subjectTypeOptions  morph aliases
     */
    public function __construct(
        #[DataCollectionOf(ActivityLogEntryData::class)]
        public PaginatedDataCollection $entries,
        public ActivityFiltersData $filters,
        public array $causerOptions,
        public array $subjectTypeOptions,
    ) {}
}
