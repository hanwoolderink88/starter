<?php

declare(strict_types=1);

namespace App\Features\Roles\Controllers;

use App\Features\Roles\Actions\UpdateRoleAction;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Requests\UpdateRoleRequest;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UpdateRoleController extends Controller
{
    public function __construct(
        private readonly UpdateRoleAction $updateRoleAction,
    ) {}

    public function __invoke(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        Gate::authorize('update', $role);

        $role = $this->updateRoleAction->handle(
            $role,
            $request->integer('version'),
            $request->validated('name'),
            $request->validated('description'),
            $request->permissions(),
            ChangeContext::fromRequest($request),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('roles.flash.updated', ['name' => $role->name])]);

        return redirect()->route('roles.index');
    }
}
