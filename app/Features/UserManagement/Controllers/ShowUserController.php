<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Controllers;

use App\Features\UserManagement\Data\ShowUserPageData;
use App\Features\UserManagement\Data\UserManagementData;
use App\Features\UserManagement\Services\UserManagementService;
use App\Http\Controllers\Controller;
use App\Infrastructure\Activity\Data\ActivityData;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShowUserController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {}

    public function __invoke(Request $request, User $user): Response
    {
        Gate::authorize('view', $user);

        $user->load('roles');

        return Inertia::render('user-management/show', new ShowUserPageData(
            user: UserManagementData::from($user),
            canUpdate: Gate::allows('update', $user),
            canImpersonate: ! ChangeContext::impersonatorFor($request) instanceof User && Gate::allows('impersonate', $user),
            activity: Gate::allows('viewAny', Activity::class)
                ? array_values(ActivityData::collect($this->userManagementService->timeline($user))->all())
                : null,
        ));
    }
}
