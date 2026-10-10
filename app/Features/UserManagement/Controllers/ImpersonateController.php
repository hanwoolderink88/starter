<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\ImpersonateUserAction;
use App\Features\UserManagement\Controllers\Concerns\SwitchesSessionUser;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Impersonation wraps the real user's session instead of replacing it: the
 * session remembers who impersonates, so every change stays traceable to them
 * and they can stop without logging in again.
 */
class ImpersonateController extends Controller
{
    use SwitchesSessionUser;

    public function __construct(
        private readonly ImpersonateUserAction $impersonateUserAction,
    ) {}

    public function __invoke(Request $request, User $user): RedirectResponse
    {
        if (ChangeContext::impersonatorFor($request) instanceof User) {
            throw new AccessDeniedHttpException;
        }

        Gate::authorize('impersonate', $user);

        $impersonator = $request->user();
        assert($impersonator instanceof User);

        $this->impersonateUserAction->handle($user, ChangeContext::fromRequest($request));

        $this->switchSessionUser($request, $user);

        $request->session()->put(ChangeContext::IMPERSONATION_SESSION_KEY, [
            'impersonator_id' => $impersonator->id,
            'impersonated_id' => $user->id,
        ]);

        return redirect()->route('dashboard');
    }
}
