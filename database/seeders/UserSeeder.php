<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\UserManagement\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('1'),
        ]);

        $admin->assignRole(Role::SuperAdmin);

        $admin2 = User::factory()->create([
            'name' => 'Test Admin 2',
            'email' => 'admin2@test.com',
            'password' => Hash::make('1'),
        ]);

        $admin2->assignRole(Role::SuperAdmin);

        User::factory(10)->create()->each(function (User $user): void {
            $user->assignRole(Role::User);
        });
    }
}
