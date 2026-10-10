<?php

declare(strict_types=1);

namespace App\Features\Activity\Policies;

use App\Features\Roles\Enums\Permission;
use App\Models\User;

/**
 * The log can be read, never changed: there is deliberately no ability to
 * update or delete an entry.
 */
class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewActivity->value);
    }
}
