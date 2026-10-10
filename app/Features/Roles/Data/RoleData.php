<?php

declare(strict_types=1);

namespace App\Features\Roles\Data;

use App\Features\Roles\Models\Role;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A role as the admin screens show it.
 */
#[TypeScript]
class RoleData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public bool $isActive,
        public bool $isProtected,
        public int $permissionCount,
        public int $userCount,
        /** The version an update must be based on. */
        public int $version,
    ) {}

    /**
     * Uses the `*_count` attributes when the query loaded them, and counts
     * through the relations otherwise.
     */
    public static function fromRole(Role $role): self
    {
        return new self(
            id: $role->id,
            name: $role->name,
            description: $role->description,
            isActive: $role->is_active,
            isProtected: $role->is_protected,
            permissionCount: self::count($role, 'permissions_count', fn (): int => $role->permissions()->count()),
            userCount: self::count($role, 'users_count', fn (): int => $role->users()->count()),
            version: $role->version,
        );
    }

    /**
     * @param  callable(): int  $fallback
     */
    private static function count(Role $role, string $attribute, callable $fallback): int
    {
        $count = $role->getAttribute($attribute);

        return is_numeric($count) ? (int) $count : $fallback();
    }
}
