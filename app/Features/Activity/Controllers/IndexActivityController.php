<?php

declare(strict_types=1);

namespace App\Features\Activity\Controllers;

use App\Features\Activity\Data\ActivityFiltersData;
use App\Features\Activity\Data\ActivityLogEntryData;
use App\Features\Activity\Data\ActivityPageData;
use App\Features\Activity\Services\ActivityLogService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class IndexActivityController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', Activity::class);

        $subjectTypes = $this->activityLogService->subjectTypes();

        /** @var array<string, mixed> $query */
        $query = $request->query();
        $filters = ActivityFiltersData::fromInput($query, $subjectTypes);

        return Inertia::render('activity/index', new ActivityPageData(
            entries: ActivityLogEntryData::collect(
                $this->activityLogService->search($filters),
                PaginatedDataCollection::class,
            ),
            filters: $filters,
            causerOptions: $this->activityLogService->causers(),
            subjectTypeOptions: $subjectTypes,
        ));
    }
}
