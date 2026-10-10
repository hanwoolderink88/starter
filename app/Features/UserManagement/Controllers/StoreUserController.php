<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\CreateUserAction;
use App\Features\UserManagement\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StoreUserController extends Controller
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
    ) {}

    public function __invoke(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $roleIds = $request->roleIds() ?? [];

        if ($roleIds !== []) {
            Gate::authorize('assignRoles', User::class);
        }

        $this->createUserAction->handle(
            $request->validated('name'),
            $request->validated('email'),
            $roleIds,
            ChangeContext::fromRequest($request),
        );

        return redirect()->route('users.index');
    }
}
