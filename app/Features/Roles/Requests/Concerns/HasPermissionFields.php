<?php

declare(strict_types=1);

namespace App\Features\Roles\Requests\Concerns;

use App\Features\Roles\Enums\Permission;
use Illuminate\Validation\Rule;

/**
 * The fields of the role forms: a name, an optional description and the set
 * of permissions (left out or empty means none).
 */
trait HasPermissionFields
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function permissionRules(): array
    {
        return [
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['distinct', Rule::enum(Permission::class)],
        ];
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        /** @var array<array-key, string> $values */
        $values = $this->validated('permissions', []);

        return array_values(array_map(Permission::from(...), $values));
    }
}
