<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Data;

use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Services\CollaborativeFields;
use App\Infrastructure\Collaboration\Services\FieldLockService;
use App\Infrastructure\Collaboration\Services\FieldRevisionService;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * One collaborative field of a record, as an edit page receives it: its value,
 * whether the viewer may edit it, and who holds it right now.
 */
#[TypeScript]
class CollaborativeFieldData extends Data
{
    public function __construct(
        /** The record's morph alias, for the field's routes. */
        public string $type,
        public int $id,
        public string $field,
        public string $value,
        public bool $multiline,
        public bool $canEdit,
        public ?FieldLockData $lock,
        public int $revisionCount,
    ) {}

    /**
     * Every collaborative field of the record, by field name.
     *
     * @param  CollaborativeModel&Model  $model
     * @param  string|null  $clientId  the tab that asks (`X-Client-Id`), to tell it its own lock
     * @return array<string, self>
     */
    public static function forModel(CollaborativeModel $model, User $viewer, ?string $clientId): array
    {
        $fields = app(CollaborativeFields::class);
        $locks = app(FieldLockService::class)->activeFor($model);
        $revisionCounts = app(FieldRevisionService::class)->counts($model);
        $canEdit = Gate::forUser($viewer)->allows('update', $model);
        $key = $model->getKey();

        $data = [];

        foreach ($model->collaborativeFields() as $field => $definition) {
            $lock = $locks->get($field);

            $data[$field] = new self(
                type: $model->getMorphClass(),
                id: is_int($key) ? $key : (int) $key,
                field: $field,
                value: $fields->value($model, $field),
                multiline: $definition->multiline,
                canEdit: $canEdit,
                lock: $lock === null ? null : FieldLockData::fromLock($lock, $viewer, $clientId),
                revisionCount: $revisionCounts[$field] ?? 0,
            );
        }

        return $data;
    }
}
