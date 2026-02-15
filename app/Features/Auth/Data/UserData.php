<?php

declare(strict_types=1);

namespace App\Features\Auth\Data;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $avatar,
        public ?string $email_verified_at,
        public ?bool $two_factor_enabled,
        public string $created_at,
        public string $updated_at,
    ) {}
}
