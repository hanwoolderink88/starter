<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Notifications\InvitationNotification;
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

        $user = $this->userManagementService->store(
            $request->validated('name'),
            $request->validated('email'),
            Role::from($request->validated('role')),
        );

        // TODO: Uncomment when Task 4 creates invitation.accept route
        // $user->notify(new InvitationNotification($user));

        return redirect()->route('users.index');
    }
}
