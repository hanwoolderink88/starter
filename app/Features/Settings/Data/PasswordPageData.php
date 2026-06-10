<?php

declare(strict_types=1);

namespace App\Features\Settings\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PasswordPageData extends Data
{
    public function __construct(
        public string $passwordRules,
    ) {}
}
