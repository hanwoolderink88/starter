<?php

declare(strict_types=1);

namespace App\Data;

use App\Features\Auth\Data\AuthData;
use App\Features\UserManagement\Enums\Permission;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class SharedData extends Data
{
    /**
     * @param  array<int, Permission>  $permissions
     */
    public function __construct(
        public string $name,
        public AuthData $auth,
        #[TypeScriptType('\App\Features\UserManagement\Enums\Permission[]')]
        public array $permissions,
        public bool $sidebarOpen,
    ) {}
}
