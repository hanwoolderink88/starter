<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Data\UserManagementData;
use App\Features\UserManagement\Data\UsersPageData;
use App\Features\UserManagement\Enums\Permission;
use App\Features\UserManagement\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\DataCollection;

class IndexUsersController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $users = $this->userManagementService->all();

        $currentUser = $request->user();
        assert($currentUser instanceof User);

        return Inertia::render('user-management/index', new UsersPageData(
            users: UserManagementData::collect($users, DataCollection::class),
            canCreate: Gate::allows('create', User::class),
            canImpersonate: $currentUser->can(Permission::ImpersonateUsers->value),
        ));
    }
}
