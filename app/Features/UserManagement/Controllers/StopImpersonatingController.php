<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\StopImpersonatingAction;
use App\Features\UserManagement\Controllers\Concerns\StopsImpersonating;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StopImpersonatingController extends Controller
{
    use StopsImpersonating;

    public function __construct(
        private readonly StopImpersonatingAction $stopImpersonatingAction,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $impersonator = ChangeContext::impersonatorFor($request);
        $impersonated = $request->user();

        if (! $impersonator instanceof User || ! $impersonated instanceof User) {
            throw new AccessDeniedHttpException;
        }

        $this->stopImpersonating($this->stopImpersonatingAction, $request, $impersonator, $impersonated);

        return redirect()->route('users.show', $impersonated);
    }
}
