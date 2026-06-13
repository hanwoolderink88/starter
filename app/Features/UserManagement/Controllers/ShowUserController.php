<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Data\ShowUserPageData;
use App\Features\UserManagement\Data\UserManagementData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShowUserController extends Controller
{
    public function __invoke(User $user): Response
    {
        Gate::authorize('view', $user);

        $user->load('roles');

        return Inertia::render('user-management/show', new ShowUserPageData(
            user: UserManagementData::from($user),
            canUpdate: Gate::allows('update', $user),
        ));
    }
}
