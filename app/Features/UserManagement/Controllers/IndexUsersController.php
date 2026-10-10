<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\Roles\Contracts\ListsAssignableRoles;
use App\Features\UserManagement\Data\UserFiltersData;
use App\Features\UserManagement\Data\UserManagementData;
use App\Features\UserManagement\Data\UserSortData;
use App\Features\UserManagement\Data\UsersPageData;
use App\Features\UserManagement\Enums\SortDirection;
use App\Features\UserManagement\Enums\UserSortColumn;
use App\Features\UserManagement\Enums\UserStatus;
use App\Features\UserManagement\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ChangeContext;
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
        private readonly ListsAssignableRoles $assignableRoles,
    ) {}

    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $search = $request->string('search')->trim()->toString();

        $filters = new UserFiltersData(
            search: $search === '' ? null : $search,
            roleId: $request->integer('role') > 0 ? $request->integer('role') : null,
            status: $request->enum('status', UserStatus::class),
        );

        $sort = new UserSortData(
            column: $request->enum('sort', UserSortColumn::class) ?? UserSortColumn::Name,
            direction: $request->enum('direction', SortDirection::class) ?? SortDirection::Asc,
        );

        $users = $this->userManagementService->paginate($filters, $sort);

        // Impersonating is not nested, so nobody is offered while it lasts.
        // Computed before the collect() below, which maps the paginator's
        // items into Data objects in place.
        $impersonatableIds = ChangeContext::impersonatorFor($request) instanceof User ? [] : array_values(array_map(
            fn (User $user): int => $user->id,
            array_filter($users->items(), fn (User $user): bool => Gate::allows('impersonate', $user)),
        ));

        return Inertia::render('user-management/index', new UsersPageData(
            users: UserManagementData::collect($users, PaginatedDataCollection::class),
            filters: $filters,
            sort: $sort,
            roleOptions: $this->assignableRoles->assignable(),
            statusOptions: UserStatus::options(),
            canCreate: Gate::allows('create', User::class),
            impersonatableIds: $impersonatableIds,
            restoreDays: (int) config('privacy.anonymize_deleted_users_after_days'),
        ));
    }
}
