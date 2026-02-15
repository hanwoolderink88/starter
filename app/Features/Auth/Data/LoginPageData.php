<?php

declare(strict_types=1);

namespace App\Features\Auth\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class LoginPageData extends Data
{
    public function __construct(
        public bool $canResetPassword,
        public bool $canRegister,
        public ?string $status = null,
    ) {}
}
