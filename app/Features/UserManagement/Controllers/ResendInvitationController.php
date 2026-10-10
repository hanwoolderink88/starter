<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\ResendInvitationAction;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResendInvitationController extends Controller
{
    public function __construct(
        private readonly ResendInvitationAction $resendInvitationAction,
    ) {}

    public function __invoke(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('create', User::class);

        if ($user->password !== null) {
            return back()->with('error', __('users.invitation.already_set_up'));
        }

        $this->resendInvitationAction->handle($user, ChangeContext::fromRequest($request));

        return back()->with('success', __('users.invitation.resent'));
    }
}
