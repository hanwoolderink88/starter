<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Data\UserFormPageData;
use App\Features\UserManagement\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CreateUserController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('user-management/create', new UserFormPageData(
            user: null,
            roles: Role::options(),
        ));
    }
}
