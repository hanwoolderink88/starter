<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Requests\Concerns;

use App\Features\Roles\Rules\ActiveRole;

/**
 * The roles of the user forms. Left out means "unchanged" (null); present,
 * also as `[]`, means "exactly this set" of active roles.
 */
trait HasRoleFields
{
    /**
     * @return array<string, array<mixed>>
     */
    protected function roleRules(): array
    {
        return [
            'role_ids' => ['sometimes', 'array'],
            'role_ids.*' => ['distinct', new ActiveRole],
        ];
    }

    /**
     * @return list<int>|null
     */
    public function roleIds(): ?array
    {
        if (! $this->has('role_ids')) {
            return null;
        }

        /** @var array<array-key, int|string> $ids */
        $ids = $this->validated('role_ids', []);

        return array_values(array_map(intval(...), $ids));
    }
}
