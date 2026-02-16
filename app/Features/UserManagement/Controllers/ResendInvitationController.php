<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Notifications\InvitationNotification;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ResendInvitationController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('create', User::class);

        if ($user->password !== null) {
            return back()->with('error', 'Cannot resend invitation to a user who has already set their password.');
        }

        $user->notify(new InvitationNotification($user));

        return back()->with('success', 'Invitation email has been resent.');
    }
}
