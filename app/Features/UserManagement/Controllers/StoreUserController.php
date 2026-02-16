<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Requests\StoreUserRequest;
use App\Features\UserManagement\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class StoreUserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function __invoke(StoreUserRequest $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $this->userManagementService->store(
            $request->validated('name'),
            $request->validated('email'),
            Role::from($request->validated('role')),
        );

        return redirect()->route('users.index');
    }
}
