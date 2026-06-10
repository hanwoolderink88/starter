<?php

declare(strict_types=1);

namespace App\Features\Settings\Controllers;

use App\Features\Settings\Data\PasswordPageData;
use App\Features\Settings\Requests\PasswordUpdateRequest;
use App\Features\Settings\Services\PasswordService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function __construct(
        private readonly PasswordService $passwordService,
    ) {}

    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        $password = Password::defaults();
        assert($password instanceof Password);

        return Inertia::render('settings/password', new PasswordPageData(
            passwordRules: $password->toPasswordRulesString(),
        ));
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $this->passwordService->update($user, $request->validated('password'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Password updated.')]);

        return back();
    }
}
