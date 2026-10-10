<?php

declare(strict_types=1);

namespace App\Features\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocaleUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(config('app.supported_locales'))],
        ];
    }
}
