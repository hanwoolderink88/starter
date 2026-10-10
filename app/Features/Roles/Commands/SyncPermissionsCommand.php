<?php

declare(strict_types=1);

namespace App\Features\Roles\Commands;

use App\Features\Roles\Actions\SyncPermissionsAction;
use App\Infrastructure\Activity\Data\ChangeContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Run on every deploy: creates the permissions a new release added to the
 * catalogue and gives them to the administrator role.
 */
#[Signature('permissions:sync')]
#[Description('Create missing permissions and give the administrator role every permission')]
class SyncPermissionsCommand extends Command
{
    public function handle(SyncPermissionsAction $syncPermissionsAction): int
    {
        $syncPermissionsAction->handle(ChangeContext::system());

        $this->components->info('Permissions are in sync.');

        return self::SUCCESS;
    }
}
