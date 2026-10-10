<?php

declare(strict_types=1);

namespace App\Features\Roles\Requests;

use App\Features\Roles\Requests\Concerns\HasPermissionFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    use HasPermissionFields;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            ...$this->permissionRules(),
        ];
    }
}
