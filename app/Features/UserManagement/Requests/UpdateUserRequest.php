<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Requests;

use App\Features\UserManagement\Requests\Concerns\HasRoleFields;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    use HasRoleFields;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        assert($user instanceof User);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->withoutTrashed()->ignore($user->id)],
            'version' => ['required', 'integer', 'min:1'],
            ...$this->roleRules(),
        ];
    }
}
