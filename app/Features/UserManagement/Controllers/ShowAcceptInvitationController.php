<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowAcceptInvitationController extends Controller
{
    public function __invoke(Request $request, User $user): Response|\Illuminate\Http\RedirectResponse
    {
        if ($user->password !== null) {
            return redirect(route('login'))->with('info', 'Invitation already accepted. Please log in.');
        }

        return Inertia::render('auth/accept-invitation', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
