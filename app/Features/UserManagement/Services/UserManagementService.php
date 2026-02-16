<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Services;

use App\Features\UserManagement\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserManagementService
{
    /**
     * @return Collection<int, User>
     */
    public function all(): Collection
    {
        return User::query()->with('roles')->orderBy('name')->get();
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
