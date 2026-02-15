<?php

declare(strict_types=1);

namespace App\Features\Auth\Actions;

use App\Features\Auth\Concerns\PasswordValidationRules;
use App\Features\Auth\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

/**
 * Validates input and creates a new user during registration.
 *
 * Note: This class implements Fortify's CreatesNewUsers contract,
 * which requires the `create()` method signature. Do not rename
 * to `handle()` or rename the class to `CreateNewUserAction`.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);
    }
}
