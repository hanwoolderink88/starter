<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Requests\AcceptInvitationRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StoreAcceptInvitationController extends Controller
{
    public function __invoke(AcceptInvitationRequest $request, User $user): RedirectResponse
    {
        if ($user->password !== null) {
            return redirect(route('login'))->with('info', 'Invitation already accepted.');
        }

        $user->update([
            'password' => $request->validated('password'),
            'email_verified_at' => now(),
        ]);

        Auth::login($user);

        return redirect('/dashboard');
    }
}
