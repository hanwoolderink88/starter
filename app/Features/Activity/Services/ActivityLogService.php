<?php

declare(strict_types=1);

namespace App\Features\Activity\Services;

use App\Features\Activity\Data\ActivityCauserData;
use App\Features\Activity\Data\ActivityFiltersData;
use App\Infrastructure\Activity\Models\Activity;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;

class ActivityLogService
{
    public const int PER_PAGE = 25;

    /**
     * @param  int|null  $page  null reads the page from the request
     * @return LengthAwarePaginator<int, Activity>
     */
    public function search(ActivityFiltersData $filters, ?int $page = null): LengthAwarePaginator
    {
        $query = Activity::query()->with('subject');

        if ($filters->event !== null) {
            $query->where('event', $filters->event);
        }

        if ($filters->source !== null) {
            $query->where('source', $filters->source);
        }

        if ($filters->causerId !== null) {
            $query->where(fn ($query) => $query
                ->where('causer_id', $filters->causerId)
                ->orWhere('impersonator_id', $filters->causerId));
        }

        if ($filters->subjectType !== null) {
            $query->where('subject_type', $filters->subjectType);
        }

        if ($filters->subjectId !== null) {
            $query->where('subject_id', $filters->subjectId);
        }

        if ($filters->from !== null) {
            $query->where('created_at', '>=', CarbonImmutable::parse($filters->from)->startOfDay());
        }

        if ($filters->to !== null) {
            $query->where('created_at', '<=', CarbonImmutable::parse($filters->to)->endOfDay());
        }

        return $query
            ->latest()
            ->latest('id')
            ->paginate(self::PER_PAGE, page: $page)
            ->withQueryString();
    }

    /**
     * Everyone who appears in the log as causer or impersonator, by their
     * name at the time of their latest entry, so deleted users stay findable.
     *
     * @return list<ActivityCauserData>
     */
    public function causers(): array
    {
        $causers = Activity::query()
            ->whereNotNull('causer_id')
            ->select(['causer_id as id', 'causer_name as name', 'id as activity_id']);

        $impersonators = Activity::query()
            ->whereNotNull('impersonator_id')
            ->select(['impersonator_id as id', 'impersonator_name as name', 'id as activity_id']);

        /** @var array<int, string> $names */
        $names = $causers->unionAll($impersonators)
            ->orderBy('activity_id')
            ->toBase()
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->id => (string) $row->name])
            ->all();

        asort($names, SORT_NATURAL | SORT_FLAG_CASE);

        return array_map(
            fn (int $id, string $name): ActivityCauserData => new ActivityCauserData($id, $name),
            array_keys($names),
            $names,
        );
    }

    /**
     * @return list<string>
     */
    public function subjectTypes(): array
    {
        return array_keys(Relation::morphMap());
    }
}
