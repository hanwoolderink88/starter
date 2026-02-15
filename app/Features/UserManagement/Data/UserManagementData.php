<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use App\Features\UserManagement\Enums\Role;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserManagementData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $email_verified_at,
        public string $created_at,
        public string $role,
    ) {}

    public static function fromUser(User $user): self
    {
        /** @var \Spatie\Permission\Models\Role|null $firstRole */
        $firstRole = $user->roles->first();

        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at?->toISOString(),
            created_at: $user->created_at?->toISOString() ?? '',
            role: $firstRole !== null ? $firstRole->name : Role::User->value,
        );
    }
}
