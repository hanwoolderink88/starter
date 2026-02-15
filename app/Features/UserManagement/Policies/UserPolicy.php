<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Policies;

use App\Features\UserManagement\Enums\Permission;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Permission::ViewUsers->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->can(Permission::ViewUsers->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Permission::CreateUsers->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->can(Permission::UpdateUsers->value);
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can(Permission::DeleteUsers->value);
    }

    public function impersonate(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false;
        }

        return $user->can(Permission::ImpersonateUsers->value);
    }
}
