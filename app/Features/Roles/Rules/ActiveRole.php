<?php

declare(strict_types=1);

namespace App\Features\Roles\Rules;

use App\Features\Roles\Models\Role;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value is the id of an active role. Shared by the user forms and the MCP
 * tools, so an inactive role is refused the same way everywhere.
 */
class ActiveRole implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $isActiveRole = (is_int($value) || (is_string($value) && ctype_digit($value)))
            && Role::query()->whereKey((int) $value)->exists();

        if (! $isActiveRole) {
            $fail('roles.validation.active_role')->translate();
        }
    }
}
