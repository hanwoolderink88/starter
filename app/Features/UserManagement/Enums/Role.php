<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Enums;

enum Role: string
{
    case User = 'user';
    case SuperAdmin = 'super-admin';

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => ucwords(str_replace('-', ' ', $role->value))])
            ->all();
    }
}
