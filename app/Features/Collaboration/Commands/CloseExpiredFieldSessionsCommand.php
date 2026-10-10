<?php

declare(strict_types=1);

namespace App\Features\Collaboration\Commands;

use App\Infrastructure\Collaboration\Actions\CloseExpiredFieldSessionsAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Scheduled every minute: closes the editing sessions that ended without a
 * goodbye, so their changes are logged and the fields are free again.
 */
#[Signature('collaboration:close-expired-sessions')]
#[Description('Close the field editing sessions that expired')]
class CloseExpiredFieldSessionsCommand extends Command
{
    public function handle(CloseExpiredFieldSessionsAction $closeExpiredFieldSessionsAction): int
    {
        $closed = $closeExpiredFieldSessionsAction->handle();

        $this->components->info("Closed {$closed} expired editing sessions.");

        return self::SUCCESS;
    }
}
