<?php

declare(strict_types=1);

namespace App\Features\Collaboration\Controllers\Concerns;

use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Data\CollaborativeFieldData;
use App\Infrastructure\Collaboration\Services\CollaborativeFields;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait ResolvesCollaborativeField
{
    /**
     * The record and field of the route, authorized against the record's own
     * policy. A record the user may not see is "not found".
     *
     * @return CollaborativeModel&Model
     */
    protected function collaborativeModel(Request $request, string $ability): CollaborativeModel
    {
        $model = app(CollaborativeFields::class)->resolve(
            (string) $request->route('type'),
            (int) $request->route('id'),
            (string) $request->route('field'),
        );

        if (! $model instanceof CollaborativeModel || Gate::denies('view', $model)) {
            throw new NotFoundHttpException;
        }

        Gate::authorize($ability, $model);

        return $model;
    }

    /**
     * The field as the editor needs it after a change.
     *
     * @param  CollaborativeModel&Model  $model
     */
    protected function fieldData(Request $request, CollaborativeModel $model): CollaborativeFieldData
    {
        $user = $request->user();
        assert($user instanceof User);

        return CollaborativeFieldData::forModel($model, $user, $request->header('X-Client-Id'))[(string) $request->route('field')];
    }
}
