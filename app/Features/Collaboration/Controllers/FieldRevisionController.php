<?php

declare(strict_types=1);

namespace App\Features\Collaboration\Controllers;

use App\Features\Collaboration\Controllers\Concerns\ResolvesCollaborativeField;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Collaboration\Actions\RestoreFieldRevisionAction;
use App\Infrastructure\Collaboration\Data\CollaborativeFieldData;
use App\Infrastructure\Collaboration\Data\FieldRevisionData;
use App\Infrastructure\Collaboration\Data\FieldRevisionDetailData;
use App\Infrastructure\Collaboration\Models\FieldRevision;
use App\Infrastructure\Collaboration\Services\CollaborativeFields;
use App\Infrastructure\Collaboration\Services\FieldRevisionService;
use App\Infrastructure\Collaboration\Services\TextDiff;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The history of a long text field: list, view and compare, restore.
 */
class FieldRevisionController extends Controller
{
    use ResolvesCollaborativeField;

    public function __construct(
        private readonly FieldRevisionService $fieldRevisionService,
        private readonly CollaborativeFields $collaborativeFields,
        private readonly TextDiff $textDiff,
        private readonly RestoreFieldRevisionAction $restoreFieldRevisionAction,
    ) {}

    /**
     * @return list<FieldRevisionData> newest first
     */
    public function index(Request $request): array
    {
        $model = $this->collaborativeModel($request, 'view');

        return array_values($this->fieldRevisionService->all($model, (string) $request->route('field'))
            ->map(fn (FieldRevision $revision): FieldRevisionData => FieldRevisionData::fromRevision($revision))
            ->all());
    }

    public function show(Request $request): FieldRevisionDetailData
    {
        $model = $this->collaborativeModel($request, 'view');
        $field = (string) $request->route('field');

        $revision = $this->fieldRevisionService->find($model, $field, (int) $request->route('number'))
            ?? throw new NotFoundHttpException;

        return new FieldRevisionDetailData(
            revision: FieldRevisionData::fromRevision($revision),
            content: $revision->content,
            diffToCurrent: $this->textDiff->between($revision->content, $this->collaborativeFields->value($model, $field)),
        );
    }

    public function restore(Request $request): CollaborativeFieldData
    {
        $model = $this->collaborativeModel($request, 'update');

        $this->restoreFieldRevisionAction->handle($model, (string) $request->route('field'), (int) $request->route('number'), ChangeContext::fromRequest($request));

        return $this->fieldData($request, $model);
    }
}
