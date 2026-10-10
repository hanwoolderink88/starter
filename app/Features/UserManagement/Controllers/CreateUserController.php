<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\Roles\Contracts\ListsAssignableRoles;
use App\Features\UserManagement\Data\UserFormPageData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CreateUserController extends Controller
{
    public function __construct(
        private readonly ListsAssignableRoles $assignableRoles,
    ) {}

    public function __invoke(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('user-management/create', new UserFormPageData(
            user: null,
            roles: $this->assignableRoles->assignable(),
            roleIds: [],
            canAssignRoles: Gate::allows('assignRoles', User::class),
        ));
    }
}
