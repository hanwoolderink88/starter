<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateUserAction
{
    public function handle(Request $request, User $target): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Auth::login($target);

        $request->session()->regenerate();
    }
}
