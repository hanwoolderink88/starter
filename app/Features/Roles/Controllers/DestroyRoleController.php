<?php

declare(strict_types=1);

namespace App\Features\Roles\Controllers;

use App\Features\Roles\Actions\DeleteRoleAction;
use App\Features\Roles\Exceptions\RoleStillAssignedException;
use App\Features\Roles\Models\Role;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DestroyRoleController extends Controller
{
    public function __construct(
        private readonly DeleteRoleAction $deleteRoleAction,
    ) {}

    public function __invoke(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('delete', $role);

        try {
            $this->deleteRoleAction->handle($role, ChangeContext::fromRequest($request));
        } catch (RoleStillAssignedException) {
            throw ValidationException::withMessages(['role' => __('roles.still_assigned', ['name' => $role->name])]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('roles.flash.deleted', ['name' => $role->name])]);

        return redirect()->route('roles.index');
    }
}
