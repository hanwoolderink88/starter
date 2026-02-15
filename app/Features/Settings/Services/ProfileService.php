<?php

declare(strict_types=1);

namespace App\Features\Settings\Services;

use App\Models\User;

class ProfileService
{
    public function update(User $user, string $name, string $email): User
    {
        $user->fill(['name' => $name, 'email' => $email]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
