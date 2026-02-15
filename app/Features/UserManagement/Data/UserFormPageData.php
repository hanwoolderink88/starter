<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserFormPageData extends Data
{
    /**
     * @param  array<string, string>  $roles
     */
    public function __construct(
        public ?UserManagementData $user,
        public array $roles,
    ) {}
}
