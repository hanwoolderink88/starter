<?php

declare(strict_types=1);

namespace App\Features\Settings\Controllers;

use App\Features\Settings\Actions\DeleteProfileAction;
use App\Features\Settings\Data\ProfilePageData;
use App\Features\Settings\Requests\ProfileDeleteRequest;
use App\Features\Settings\Requests\ProfileUpdateRequest;
use App\Features\Settings\Services\ProfileService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
        private readonly DeleteProfileAction $deleteProfileAction,
    ) {}

    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', new ProfilePageData(
            mustVerifyEmail: $request->user() instanceof MustVerifyEmail,
            status: $request->session()->get('status'),
        ));
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $this->profileService->update(
            $user,
            $request->validated('name'),
            $request->validated('email'),
        );

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $this->deleteProfileAction->handle($request, $user);

        return redirect('/');
    }
}
