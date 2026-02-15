<?php

declare(strict_types=1);

namespace App\Features\Auth\Actions;

use App\Features\Auth\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * Validates input and resets a user's forgotten password.
 *
 * Note: This class implements Fortify's ResetsUserPasswords contract,
 * which requires the `reset()` method signature. Do not rename
 * to `handle()` or rename the class to `ResetUserPasswordAction`.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
