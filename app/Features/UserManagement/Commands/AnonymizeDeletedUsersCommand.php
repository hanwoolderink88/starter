<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Commands;

use App\Features\UserManagement\Actions\AnonymizeUserAction;
use App\Features\UserManagement\Services\UserManagementService;
use App\Infrastructure\Activity\Data\ChangeContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Scheduled daily: anonymizes the users deleted longer ago than the term in
 * `privacy.anonymize_deleted_users_after_days`.
 */
#[Signature('users:anonymize')]
#[Description('Anonymize users whose term to be restored has ended')]
class AnonymizeDeletedUsersCommand extends Command
{
    public function handle(UserManagementService $userManagementService, AnonymizeUserAction $anonymizeUserAction): int
    {
        $days = config('privacy.anonymize_deleted_users_after_days');
        assert(is_int($days));

        $users = $userManagementService->dueForAnonymization(now()->subDays($days));

        foreach ($users as $user) {
            $anonymizeUserAction->handle($user, ChangeContext::system());
        }

        $this->components->info("Anonymized {$users->count()} deleted users.");

        return self::SUCCESS;
    }
}
