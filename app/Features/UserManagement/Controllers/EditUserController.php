<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Data\UserFormPageData;
use App\Features\UserManagement\Data\UserManagementData;
use App\Features\UserManagement\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EditUserController extends Controller
{
    public function __invoke(User $user): Response
    {
        Gate::authorize('update', $user);

        $user->load('roles');

        return Inertia::render('user-management/edit', new UserFormPageData(
            user: UserManagementData::from($user),
            roles: Role::options(),
        ));
    }
}
