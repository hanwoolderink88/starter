<?php

declare(strict_types=1);

namespace App\Features\Settings\Controllers;

use App\Features\Settings\Requests\PasswordUpdateRequest;
use App\Features\Settings\Services\PasswordService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
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
        return Inertia::render('settings/password');
    }

    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $this->passwordService->update($user, $request->validated('password'));

        return back();
    }
}
