<?php

declare(strict_types=1);

namespace App\Features\Collaboration\Controllers;

use App\Features\Collaboration\Controllers\Concerns\ResolvesCollaborativeField;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Collaboration\Actions\ClaimFieldAction;
use App\Infrastructure\Collaboration\Actions\HeartbeatFieldAction;
use App\Infrastructure\Collaboration\Actions\ReleaseFieldAction;
use App\Infrastructure\Collaboration\Data\CollaborativeFieldData;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Claiming, keeping and releasing a field: the editing session of one tab.
 */
class FieldLockController extends Controller
{
    use ResolvesCollaborativeField;

    public function __construct(
        private readonly ClaimFieldAction $claimFieldAction,
        private readonly HeartbeatFieldAction $heartbeatFieldAction,
        private readonly ReleaseFieldAction $releaseFieldAction,
    ) {}

    /**
     * Start editing. Returns the field with its current value, which the
     * editor continues from.
     */
    public function store(Request $request): CollaborativeFieldData
    {
        $model = $this->collaborativeModel($request, 'update');

        $this->claimFieldAction->handle($model, (string) $request->route('field'), ChangeContext::fromRequest($request));

        return $this->fieldData($request, $model);
    }

    public function update(Request $request): Response
    {
        $model = $this->collaborativeModel($request, 'update');

        $this->heartbeatFieldAction->handle($model, (string) $request->route('field'), ChangeContext::fromRequest($request));

        return response()->noContent();
    }

    public function destroy(Request $request): Response
    {
        $model = $this->collaborativeModel($request, 'update');

        $this->releaseFieldAction->handle($model, (string) $request->route('field'), ChangeContext::fromRequest($request));

        return response()->noContent();
    }
}
