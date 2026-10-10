<?php

declare(strict_types=1);

namespace App\Features\Roles\Controllers;

use App\Features\Roles\Data\RoleData;
use App\Features\Roles\Data\RolesPageData;
use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use App\Features\Roles\Services\RoleService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\LaravelData\DataCollection;

class IndexRolesController extends Controller
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function __invoke(): Response
    {
        Gate::authorize('viewAny', Role::class);

        return Inertia::render('roles/index', new RolesPageData(
            roles: RoleData::collect($this->roleService->all(), DataCollection::class),
            permissionTotal: count(Permission::cases()),
            inactiveCount: $this->roleService->counts()['inactive'],
        ));
    }
}
