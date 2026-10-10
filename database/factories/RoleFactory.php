<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Features\Roles\Enums\Permission;
use App\Features\Roles\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'guard_name' => 'web',
            'description' => fake()->sentence(),
            'is_active' => true,
            'is_protected' => false,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /**
     * @param  list<Permission>  $permissions
     */
    public function withPermissions(array $permissions): static
    {
        return $this->afterCreating(fn (Role $role) => $role->givePermissionTo($permissions));
    }
}
