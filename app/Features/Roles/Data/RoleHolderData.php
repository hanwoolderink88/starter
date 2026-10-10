<?php

declare(strict_types=1);

namespace App\Features\Roles\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * A user who has a role, named in the dialog that blocks deleting it.
 */
#[TypeScript]
class RoleHolderData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(id: $user->id, name: $user->name);
    }
}
