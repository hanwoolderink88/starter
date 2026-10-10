<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Roles\Actions\SeedDefaultRolesAction;
use App\Features\Roles\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $administrator = Role::query()->where('is_protected', true)->sole();
        $user = Role::query()->where('name', SeedDefaultRolesAction::USER_NAME)->sole();

        foreach (['admin@test.com' => 'Test Admin', 'admin2@test.com' => 'Test Admin 2'] as $email => $name) {
            User::factory()->create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('1'),
            ])->assignRole($administrator);
        }

        User::factory(10)->create()->each(function (User $model) use ($user): void {
            $model->assignRole($user);
        });
    }
}
