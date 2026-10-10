<?php

declare(strict_types=1);

namespace App\Features\Collaboration\Controllers;

use App\Features\Collaboration\Controllers\Concerns\ResolvesCollaborativeField;
use App\Features\Collaboration\Requests\SaveFieldRequest;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Collaboration\Actions\SaveFieldAction;
use App\Infrastructure\Collaboration\Data\CollaborativeFieldData;

/**
 * The autosave of the tab that holds the field.
 */
class FieldValueController extends Controller
{
    use ResolvesCollaborativeField;

    public function __construct(
        private readonly SaveFieldAction $saveFieldAction,
    ) {}

    public function __invoke(SaveFieldRequest $request): CollaborativeFieldData
    {
        $model = $this->collaborativeModel($request, 'update');

        $this->saveFieldAction->handle($model, (string) $request->route('field'), $request->value(), ChangeContext::fromRequest($request));

        return $this->fieldData($request, $model);
    }
}
