<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Requests;

use App\Features\UserManagement\Requests\Concerns\HasRoleFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use HasRoleFields;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->withoutTrashed()],
            ...$this->roleRules(),
        ];
    }
}
