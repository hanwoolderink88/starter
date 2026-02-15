<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\UserManagement\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Test User',
            'email' => 'admin@test.nl',
            'password' => Hash::make('1'),

        ]);
        $admin->assignRole(Role::SuperAdmin);

        User::factory(10)->create([
            'password' => Hash::make('1'),
        ])->each(function (User $user): void {
            $user->assignRole(Role::User);
        });
    }
}
