<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\ImpersonateUserAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ImpersonateController extends Controller
{
    public function __construct(
        private readonly ImpersonateUserAction $impersonateUserAction,
    ) {}

    public function __invoke(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            throw new AccessDeniedHttpException;
        }

        Gate::authorize('impersonate', $user);

        $this->impersonateUserAction->handle($request, $user);

        return redirect()->route('dashboard');
    }
}
