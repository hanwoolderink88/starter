<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\Roles\Actions\RevokeUserRoleAction;
use App\Features\Roles\Exceptions\LastAdministratorException;
use App\Features\UserManagement\Events\UserChanged;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Take one role away from a user, also an inactive one: saving the user form
 * keeps inactive assignments, so this is how they are removed.
 */
class RevokeUserRoleController extends Controller
{
    public function __construct(
        private readonly RevokeUserRoleAction $revokeUserRoleAction,
    ) {}

    public function __invoke(Request $request, User $user, int $role): RedirectResponse
    {
        Gate::authorize('update', $user);
        Gate::authorize('assignRoles', $user);

        $context = ChangeContext::fromRequest($request);

        try {
            $revoked = $this->revokeUserRoleAction->handle($user, $role, $context);
        } catch (LastAdministratorException) {
            throw ValidationException::withMessages(['role_ids' => __('roles.last_administrator')]);
        }

        if ($revoked) {
            UserChanged::dispatch(ResourceChangedData::fromContext($context, ResourceAction::Updated, $user->id, $user->name));
        }

        return back();
    }
}
