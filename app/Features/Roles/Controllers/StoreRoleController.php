<?php

declare(strict_types=1);

namespace App\Features\Roles\Controllers;

use App\Features\Roles\Actions\CreateRoleAction;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Requests\StoreRoleRequest;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StoreRoleController extends Controller
{
    public function __construct(
        private readonly CreateRoleAction $createRoleAction,
    ) {}

    public function __invoke(StoreRoleRequest $request): RedirectResponse
    {
        Gate::authorize('create', Role::class);

        $role = $this->createRoleAction->handle(
            $request->validated('name'),
            $request->validated('description'),
            $request->permissions(),
            ChangeContext::fromRequest($request),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('roles.flash.created', ['name' => $role->name])]);

        return redirect()->route('roles.index');
    }
}
