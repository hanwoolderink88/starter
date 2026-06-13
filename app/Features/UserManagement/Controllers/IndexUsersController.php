<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Data\UserFiltersData;
use App\Features\UserManagement\Data\UserManagementData;
use App\Features\UserManagement\Data\UserSortData;
use App\Features\UserManagement\Data\UsersPageData;
use App\Features\UserManagement\Enums\Permission;
use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Enums\SortDirection;
use App\Features\UserManagement\Enums\UserSortColumn;
use App\Features\UserManagement\Enums\UserStatus;
use App\Features\UserManagement\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class IndexUsersController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();

        $filters = new UserFiltersData(
            search: $search === '' ? null : $search,
            role: $request->enum('role', Role::class),
            status: $request->enum('status', UserStatus::class),
        );

        $sort = new UserSortData(
            column: $request->enum('sort', UserSortColumn::class) ?? UserSortColumn::Name,
            direction: $request->enum('direction', SortDirection::class) ?? SortDirection::Asc,
        );

        $currentUser = $request->user();
        assert($currentUser instanceof User);

        return Inertia::render('user-management/index', new UsersPageData(
            users: UserManagementData::collect(
                $this->userManagementService->paginate($filters, $sort),
                PaginatedDataCollection::class,
            ),
            filters: $filters,
            sort: $sort,
            roleOptions: Role::options(),
            statusOptions: UserStatus::options(),
            canCreate: Gate::allows('create', User::class),
            canImpersonate: $currentUser->can(Permission::ImpersonateUsers->value),
        ));
    }
}
