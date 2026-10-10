<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers\Concerns;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

trait SwitchesSessionUser
{
    /**
     * Make another user the one this session is logged in as.
     *
     * Deliberately neither logout() nor login(): a logout would cycle the
     * remember token and end the real user's "remember me" on every device,
     * and a login would fire the Login event and be recorded as that user
     * logging in. Switching is logged as starting or stopping an
     * impersonation instead. The session id is migrated, the password
     * confirmation and password hash of the previous user are dropped so they
     * never carry over, and the CSRF token is renewed.
     */
    protected function switchSessionUser(Request $request, User $user): void
    {
        $guard = Auth::guard('web');

        $request->session()->forget(['auth.password_confirmed_at', 'password_hash_web']);
        $request->session()->put($guard->getName(), $user->getAuthIdentifier());
        $request->session()->migrate(true);
        $request->session()->regenerateToken();

        $guard->setUser($user);
    }
}
