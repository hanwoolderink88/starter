<?php

declare(strict_types=1);

namespace App\Features\Auth\Actions;

use App\Features\Auth\Concerns\PasswordValidationRules;
use App\Features\Auth\Concerns\ProfileValidationRules;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;
use Illuminate\Http\Request;
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

    public function __construct(
        private readonly ActivityRecorder $activityRecorder,
        private readonly Request $request,
    ) {}

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

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]);

        $this->activityRecorder->record(
            ChangeContext::fromRequest($this->request, actor: $user),
            ActivityEvent::Created,
            $user,
            ActivityRecorder::diff([], ['name' => $user->name, 'email' => $user->email]),
        );

        return $user;
    }
}
