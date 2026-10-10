<?php

declare(strict_types=1);

namespace App\Features\Roles\Contracts;

use App\Features\Roles\Services\RoleAssignmentService;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;

/**
 * There is always at least one administrator. Only administrators who
 * accepted their invitation count. For features that delete users and must
 * not reach into the Roles internals.
 */
#[Bind(RoleAssignmentService::class)]
interface GuardsLastAdministrator
{
    /**
     * Whether the user has the administrator role and no other user who
     * accepted their invitation has it, so the user may neither be deleted
     * nor lose the role.
     */
    public function isLastAdministrator(User $user): bool;
}
