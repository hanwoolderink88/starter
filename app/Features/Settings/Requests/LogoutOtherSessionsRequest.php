<?php

declare(strict_types=1);

namespace App\Features\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LogoutOtherSessionsRequest extends FormRequest
{
    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'current_password:web'],
        ];
    }
}
