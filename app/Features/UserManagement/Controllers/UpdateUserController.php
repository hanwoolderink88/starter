<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\Roles\Exceptions\LastAdministratorException;
use App\Features\UserManagement\Actions\UpdateUserAction;
use App\Features\UserManagement\Requests\UpdateUserRequest;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UpdateUserController extends Controller
{
    public function __construct(
        private readonly UpdateUserAction $updateUserAction,
    ) {}

    public function __invoke(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $roleIds = $request->roleIds();

        if ($roleIds !== null) {
            Gate::authorize('assignRoles', [$user, $roleIds]);
        }

        try {
            $this->updateUserAction->handle(
                $user,
                $request->integer('version'),
                $request->validated('name'),
                $request->validated('email'),
                $roleIds,
                ChangeContext::fromRequest($request),
            );
        } catch (LastAdministratorException) {
            throw ValidationException::withMessages(['role_ids' => __('roles.last_administrator')]);
        }

        return redirect()->route('users.index');
    }
}
