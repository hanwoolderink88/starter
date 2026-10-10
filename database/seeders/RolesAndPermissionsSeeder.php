<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Features\Roles\Actions\SeedDefaultRolesAction;
use App\Infrastructure\Activity\Data\ChangeContext;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(SeedDefaultRolesAction $seedDefaultRolesAction): void
    {
        $seedDefaultRolesAction->handle(ChangeContext::system());
    }
}
