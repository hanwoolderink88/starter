<?php

declare(strict_types=1);

namespace App\Features\Auth\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ResetPasswordPageData extends Data
{
    public function __construct(
        public string $token,
        public ?string $email = null,
    ) {}
}
