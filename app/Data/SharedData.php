<?php

declare(strict_types=1);

namespace App\Data;

use App\Features\Auth\Data\AuthData;
use App\Features\Roles\Enums\Permission;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;
use Spatie\TypeScriptTransformer\Attributes\TypeScriptType;

#[TypeScript]
class SharedData extends Data
{
    /**
     * @param  array<int, Permission>  $permissions
     * @param  list<string>  $supportedLocales  the languages a user can choose
     */
    public function __construct(
        public string $name,
        public AuthData $auth,
        #[TypeScriptType('\App\Features\Roles\Enums\Permission[]')]
        public array $permissions,
        public bool $sidebarOpen,
        /** The language this request is served in. */
        public string $locale,
        public array $supportedLocales,
    ) {}
}
