<?php

declare(strict_types=1);

namespace App\Features\Roles\Data;

use App\Features\Roles\Models\Role;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A role as a form or filter offers it. Inactive roles are included with
 * `isActive` false: shown disabled, never selectable, and an existing
 * inactive assignment stays visible.
 */
#[TypeScript]
class AssignableRoleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isActive,
        /** The administrator role, which the last administrator cannot lose. */
        public bool $isProtected,
    ) {}

    public static function fromRole(Role $role): self
    {
        return new self(
            id: $role->id,
            name: $role->name,
            isActive: $role->is_active,
            isProtected: $role->is_protected,
        );
    }
}
