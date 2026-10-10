<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use App\Infrastructure\Activity\Data\ActivityData;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class ShowUserPageData extends Data
{
    /**
     * @param  list<ActivityData>|null  $activity  the newest entries about this user; null when the viewer may not read the activity log
     */
    public function __construct(
        public UserManagementData $user,
        public bool $canUpdate,
        public bool $canImpersonate,
        public ?array $activity,
    ) {}
}
