<?php

declare(strict_types=1);

namespace App\Features\Activity\Commands;

use App\Infrastructure\Activity\Services\ActivityPrivacy;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Scheduled daily: applies the activity log terms in config/privacy.php.
 */
#[Signature('activity:prune')]
#[Description('Clear network details and remove entries past their retention term')]
class PruneActivityCommand extends Command
{
    public function handle(ActivityPrivacy $activityPrivacy): int
    {
        $networkDays = config('privacy.activity_network_details_days');
        assert(is_int($networkDays));

        $cleared = $activityPrivacy->clearNetworkDetailsBefore(now()->subDays($networkDays));
        $this->components->info("Cleared network details of {$cleared} entries.");

        $retentionDays = config('privacy.activity_retention_days');

        if (is_int($retentionDays)) {
            $deleted = $activityPrivacy->deleteBefore(now()->subDays($retentionDays));
            $this->components->info("Deleted {$deleted} entries past the retention term.");
        }

        return self::SUCCESS;
    }
}
