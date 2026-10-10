<?php

declare(strict_types=1);

namespace App\Features\Collaboration\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Only the shape: the field's own rules are checked by SaveFieldAction, so a
 * tool and the browser get the same answer.
 */
class SaveFieldRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'value' => ['present', 'nullable', 'string'],
        ];
    }

    public function value(): string
    {
        $value = $this->validated('value');

        return is_string($value) ? $value : '';
    }
}
