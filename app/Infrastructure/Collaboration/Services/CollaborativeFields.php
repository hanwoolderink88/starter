<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Services;

use App\Infrastructure\Collaboration\Contracts\CollaborativeModel;
use App\Infrastructure\Collaboration\Data\CollaborativeField;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Reading, validating and writing the value of a collaborative field in the
 * record's own column.
 */
class CollaborativeFields
{
    /**
     * The record behind a morph alias and id, when it is collaborative and
     * has the field. Global scopes are ignored, so an inactive role, say, can
     * still be edited.
     *
     * @return (CollaborativeModel&Model)|null
     */
    public function resolve(string $type, int $id, string $field): ?CollaborativeModel
    {
        $class = Relation::getMorphedModel($type);

        if ($class === null || ! is_a($class, CollaborativeModel::class, true)) {
            return null;
        }

        $model = $class::query()->withoutGlobalScopes()->find($id);

        return $model instanceof CollaborativeModel && $this->definition($model, $field) instanceof CollaborativeField ? $model : null;
    }

    public function definition(CollaborativeModel $model, string $field): ?CollaborativeField
    {
        return $model->collaborativeFields()[$field] ?? null;
    }

    /**
     * The value as the editor shows it: null is an empty text.
     */
    public function value(Model $model, string $field): string
    {
        $value = $model->getAttribute($field);

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Check the value against the field's own rules and the length limit.
     *
     * @throws ValidationException on `value`
     */
    public function validate(CollaborativeField $definition, string $value): void
    {
        Validator::make(
            ['value' => $value === '' ? null : $value],
            ['value' => [...$definition->rules, 'max:'.config()->integer('collaboration.max_length')]],
            attributes: ['value' => mb_strtolower(__($definition->label))],
        )->validate();
    }

    /**
     * Write the value without touching the record's version: a form for the
     * other fields of the record stays current. An empty text is stored as
     * null.
     */
    public function write(Model $model, string $field, string $value): void
    {
        $model->forceFill([$field => $value === '' ? null : $value])->save();
    }
}
