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

        // Not checked against a version (it is the user's own form), but an
        // administrator's form opened before it must not overwrite it.
        if ($user->wasChanged(['name', 'email'])) {
            $user->bumpVersion();
        }

        return $user;
    }

    public function updateLocale(User $user, string $locale): User
    {
        $user->forceFill(['locale' => $locale])->save();

        return $user;
    }
}
