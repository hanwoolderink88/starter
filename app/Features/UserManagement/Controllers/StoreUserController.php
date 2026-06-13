<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Actions\CreateUserAction;
use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Requests\StoreUserRequest;
use App\Http\Controllers\Controller;
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

        $actor = $request->user();
        assert($actor instanceof User);

        $this->createUserAction->handle(
            $request->validated('name'),
            $request->validated('email'),
            Role::from($request->validated('role')),
            $actor,
            $request->header('X-Client-Id'),
        );

        return redirect()->route('users.index');
    }
}
