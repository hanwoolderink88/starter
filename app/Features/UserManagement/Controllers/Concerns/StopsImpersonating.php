<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers\Concerns;

use App\Features\UserManagement\Actions\StopImpersonatingAction;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\Request;

trait StopsImpersonating
{
    use SwitchesSessionUser;

    /**
     * End the impersonation of this session, log it with the impersonator as
     * the actor, and hand the session back to the impersonator.
     */
    protected function stopImpersonating(StopImpersonatingAction $stopImpersonatingAction, Request $request, User $impersonator, User $impersonated): void
    {
        $request->session()->forget(ChangeContext::IMPERSONATION_SESSION_KEY);

        $stopImpersonatingAction->handle($impersonated, ChangeContext::fromRequest($request, actor: $impersonator));

        $this->switchSessionUser($request, $impersonator);
    }
}
