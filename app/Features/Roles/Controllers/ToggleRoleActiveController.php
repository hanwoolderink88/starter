<?php

declare(strict_types=1);

namespace App\Features\Roles\Controllers;

use App\Features\Roles\Actions\ToggleRoleActiveAction;
use App\Features\Roles\Models\Role;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ToggleRoleActiveController extends Controller
{
    public function __construct(
        private readonly ToggleRoleActiveAction $toggleRoleActiveAction,
    ) {}

    public function __invoke(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('toggleActive', $role);

        $role = $this->toggleRoleActiveAction->handle($role, ChangeContext::fromRequest($request));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __($role->is_active ? 'roles.flash.activated' : 'roles.flash.deactivated', ['name' => $role->name]),
        ]);

        return redirect()->back();
    }
}
