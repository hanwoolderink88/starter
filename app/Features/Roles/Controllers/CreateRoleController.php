<?php

declare(strict_types=1);

namespace App\Features\Roles\Controllers;

use App\Features\Roles\Data\PermissionGroupData;
use App\Features\Roles\Data\RoleFormPageData;
use App\Features\Roles\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CreateRoleController extends Controller
{
    public function __invoke(): Response
    {
        Gate::authorize('create', Role::class);

        return Inertia::render('roles/create', new RoleFormPageData(
            role: null,
            permissionGroups: PermissionGroupData::catalogue(),
            rolePermissions: [],
        ));
    }
}
