<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Requests\UpdateUserRequest;
use App\Features\UserManagement\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UpdateUserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function __invoke(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $this->userManagementService->update(
            $user,
            $request->validated('name'),
            $request->validated('email'),
            Role::from($request->validated('role')),
        );

        return redirect()->route('users.index');
    }
}
