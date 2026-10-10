<?php

declare(strict_types=1);

namespace App\Features\Auth\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class AuthData extends Data
{
    public function __construct(
        public ?UserData $user,
        /**
         * The real user behind this session while it impersonates `user`,
         * otherwise null.
         */
        public ?string $impersonatorName = null,
    ) {}
}
