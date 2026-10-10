<?php

declare(strict_types=1);

namespace App\Features\Settings\Controllers;

use App\Features\Settings\Actions\UpdatePasswordAction;
use App\Features\Settings\Data\PasswordPageData;
use App\Features\Settings\Requests\PasswordUpdateRequest;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function __construct(
        private readonly UpdatePasswordAction $updatePasswordAction,
    ) {}

    /**
     * Show the user's password settings page.
     */
    public function edit(): Response
    {
        $password = Password::defaults();

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

        $this->updatePasswordAction->handle($user, $request->validated('password'), ChangeContext::fromRequest($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('settings.flash.password_updated')]);

        return back();
    }
}
