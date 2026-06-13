<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
enum UserStatus: string
{
    case Active = 'active';
    case Invited = 'invited';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => ucfirst($status->value)])
            ->all();
    }
}
