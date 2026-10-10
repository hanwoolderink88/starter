<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use App\Features\UserManagement\Enums\UserStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserFiltersData extends Data
{
    public function __construct(
        public ?string $search = null,
        /** The id of a role, active or not. */
        public ?int $roleId = null,
        public ?UserStatus $status = null,
    ) {}
}
