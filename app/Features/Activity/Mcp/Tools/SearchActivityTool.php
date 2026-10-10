<?php

declare(strict_types=1);

namespace App\Features\Activity\Mcp\Tools;

use App\Features\Activity\Data\ActivityFiltersData;
use App\Features\Activity\Data\ActivityLogEntryData;
use App\Features\Activity\Services\ActivityLogService;
use App\Features\Roles\Enums\Permission;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Activity\Models\Activity;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Override;

/**
 * The activity log for an agent, with the same filters as /activity. IP
 * addresses and user agents are left out: an agent never needs them to answer
 * who did what, and they are personal data that would leave the application.
 */
#[Name('search_activity')]
#[Description('Search the activity log: who did what, when and through which surface (web, mcp, system). Filter by event, source, the user who did it (also as impersonator), the subject and a period. Newest first, 25 per page.')]
#[IsReadOnly]
class SearchActivityTool extends Tool
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {}

    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->can(Permission::ViewActivity->value) ?? false;
    }

    /**
     * @return array<string, Type>
     */
    #[Override]
    public function schema(JsonSchema $schema): array
    {
        return [
            'event' => $schema->string()
                ->description('Only this kind of event.')
                ->enum(array_column(ActivityEvent::cases(), 'value')),
            'source' => $schema->string()
                ->description('Only changes made through this surface.')
                ->enum(array_column(ActivitySource::cases(), 'value')),
            'causer_id' => $schema->integer()
                ->description('Only what this user did, also while impersonating someone.'),
            'subject_type' => $schema->string()
                ->description('Only entries about this kind of record.')
                ->enum($this->activityLogService->subjectTypes()),
            'subject_id' => $schema->integer()
                ->description('Only entries about the record with this id (with subject_type).'),
            'from' => $schema->string()
                ->description('From this day, inclusive, as YYYY-MM-DD.'),
            'to' => $schema->string()
                ->description('Up to this day, inclusive, as YYYY-MM-DD.'),
            'page' => $schema->integer()
                ->description('The page, starting at 1.'),
        ];
    }

    public function handle(Request $request): Response
    {
        Gate::authorize('viewAny', Activity::class);

        $validated = $request->validate([
            'event' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
            'causer_id' => ['nullable', 'integer'],
            'subject_type' => ['nullable', 'string'],
            'subject_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $filters = ActivityFiltersData::fromInput($validated, $this->activityLogService->subjectTypes());
        $page = isset($validated['page']) ? (int) $validated['page'] : 1;
        $paginator = $this->activityLogService->search($filters, $page);

        return Response::json([
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'entries' => array_map(function (Activity $activity): array {
                $entry = ActivityLogEntryData::fromActivity($activity);

                return [
                    'id' => $entry->activity->id,
                    'at' => $entry->activity->createdAt,
                    'event' => $entry->activity->event->value,
                    'subject_type' => $entry->activity->subjectType,
                    'subject_id' => $entry->activity->subjectId,
                    'subject_name' => $entry->subjectName,
                    'causer_id' => $entry->activity->causerId,
                    'causer_name' => $entry->activity->causerName,
                    'impersonator_name' => $entry->activity->impersonatorName,
                    'source' => $entry->activity->source->value,
                    'changes' => $entry->activity->snapshot !== null
                        ? ['snapshot' => $entry->activity->snapshot]
                        : $entry->activity->changes,
                ];
            }, $paginator->items()),
        ]);
    }
}
