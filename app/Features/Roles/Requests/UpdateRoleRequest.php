<?php

declare(strict_types=1);

namespace App\Features\Roles\Requests;

use App\Features\Roles\Requests\Concerns\HasPermissionFields;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    use HasPermissionFields;

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        // The name and description are edited together as collaborative
        // fields; this form saves the permissions.
        return [
            'version' => ['required', 'integer', 'min:1'],
            ...$this->permissionRules(),
        ];
    }
}
