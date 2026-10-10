<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\AcceptInvitationAction;
use App\Features\UserManagement\Requests\AcceptInvitationRequest;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StoreAcceptInvitationController extends Controller
{
    public function __construct(
        private readonly AcceptInvitationAction $acceptInvitationAction,
    ) {}

    public function __invoke(AcceptInvitationRequest $request, User $user): RedirectResponse
    {
        if ($user->password !== null) {
            return redirect(route('login'))->with('info', __('users.invitation.already_accepted'));
        }

        $this->acceptInvitationAction->handle(
            $user,
            $request->validated('password'),
            ChangeContext::fromRequest($request, actor: $user),
        );

        Auth::login($user);

        return redirect('/dashboard');
    }
}
