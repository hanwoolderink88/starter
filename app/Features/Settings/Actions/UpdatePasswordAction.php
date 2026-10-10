<?php

declare(strict_types=1);

namespace App\Features\Settings\Actions;

use App\Features\Settings\Services\PasswordService;
use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Services\ActivityRecorder;
use App\Models\User;

class UpdatePasswordAction
{
    public function __construct(
        private readonly PasswordService $passwordService,
        private readonly ActivityRecorder $activityRecorder,
    ) {}

    public function handle(User $user, string $password, ChangeContext $context): void
    {
        $this->passwordService->update($user, $password);

        $this->activityRecorder->record($context, ActivityEvent::PasswordChanged, $user);
    }
}
