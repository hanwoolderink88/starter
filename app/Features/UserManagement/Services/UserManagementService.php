<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Services;

use App\Features\UserManagement\Data\UserFiltersData;
use App\Features\UserManagement\Data\UserSortData;
use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class UserManagementService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserFiltersData $filters, UserSortData $sort): LengthAwarePaginator
    {
        $query = User::query()->with('roles');

        $search = $filters->search;
        if ($search !== null) {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $role = $filters->role;
        if ($role !== null) {
            $query->whereHas('roles', function (Builder $query) use ($role): void {
                $query->where('name', $role->value);
            });
        }

        if ($filters->status !== null) {
            match ($filters->status) {
                UserStatus::Active => $query->whereNotNull('password'),
                UserStatus::Invited => $query->whereNull('password'),
            };
        }

        return $query
            ->orderBy($sort->column->value, $sort->direction->value)
            ->paginate(10)
            ->withQueryString();
    }

    public function store(string $name, string $email, Role $role): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
        ]);

        $user->assignRole($role);

        return $user;
    }

    public function update(User $user, string $name, string $email, Role $role): User
    {
        $user->update([
            'name' => $name,
            'email' => $email,
        ]);

        $user->syncRoles([$role]);

        return $user->refresh();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
