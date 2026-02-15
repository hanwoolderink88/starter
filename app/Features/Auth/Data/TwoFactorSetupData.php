<?php

declare(strict_types=1);

namespace App\Features\Auth\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TwoFactorSetupData extends Data
{
    public function __construct(public string $svg, public string $url) {}
}
