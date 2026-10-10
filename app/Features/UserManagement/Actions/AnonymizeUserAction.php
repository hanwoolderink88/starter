<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Actions;

use App\Features\UserManagement\Services\UserManagementService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityPrivacy;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The right to be forgotten: replace everything that identifies a deleted
 * user, in their account and in the activity log, with "Deleted user #id".
 * The log keeps every entry, so it still proves what happened. Irreversible.
 */
class AnonymizeUserAction
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
        private readonly ActivityPrivacy $activityPrivacy,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    public function handle(User $user, ChangeContext $context): void
    {
        $label = __('activity.anonymized_user', ['id' => $user->id], config('app.locale'));

        DB::transaction(function () use ($user, $label, $context): void {
            $this->userManagementService->anonymize($user, $label);

            $this->activityPrivacy->anonymizeUser($user->id, $user->getMorphClass(), $label);

            $this->activityRecorder->record($context, ActivityEvent::Anonymized, $user);
        });
    }
}
