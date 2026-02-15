<?php

declare(strict_types=1);

namespace App\Features\Settings\Services;

use App\Models\User;

class PasswordService
{
    public function update(User $user, string $password): User
    {
        $user->update(['password' => $password]);

        return $user;
    }
}
