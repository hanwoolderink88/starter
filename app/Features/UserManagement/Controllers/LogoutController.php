<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\StopImpersonatingAction;
use App\Features\UserManagement\Controllers\Concerns\StopsImpersonating;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/**
 * Replaces Fortify's logout route. Logging out while impersonating first stops
 * the impersonation, so that is logged too, and then logs the impersonator out
 * as if they had logged out themselves: their remember token is cycled, never
 * the impersonated user's.
 */
class LogoutController extends Controller
{
    use StopsImpersonating;

    public function __construct(
        private readonly StopImpersonatingAction $stopImpersonatingAction,
        private readonly AuthenticatedSessionController $authenticatedSessionController,
    ) {}

    public function __invoke(Request $request): LogoutResponse
    {
        $impersonator = ChangeContext::impersonatorFor($request);
        $impersonated = $request->user();

        if ($impersonator instanceof User && $impersonated instanceof User) {
            $this->stopImpersonating($this->stopImpersonatingAction, $request, $impersonator, $impersonated);
        }

        return $this->authenticatedSessionController->destroy($request);
    }
}
