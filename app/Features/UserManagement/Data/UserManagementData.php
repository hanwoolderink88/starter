<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Data;

use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserManagementData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $email_verified_at,
        public string $created_at,
        public string $created_at_display,
        /** @var list<string> the names of the user's active roles */
        public array $roles,
        public bool $has_password,
        /** The version an update must be based on. */
        public int $version,
        /** The day the deleted user is anonymized, after which they cannot be restored; null for a user who is not deleted. */
        public ?string $anonymizes_on_display = null,
    ) {}

    public static function fromUser(User $user): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            email_verified_at: $user->email_verified_at?->toISOString(),
            created_at: $user->created_at?->toISOString() ?? '',
            created_at_display: $user->created_at?->isoFormat('ll') ?? '',
            roles: array_values($user->roles->sortBy('name')->pluck('name')->all()),
            has_password: $user->password !== null,
            version: $user->version,
            anonymizes_on_display: $user->trashed()
                ? $user->deleted_at?->addDays(self::anonymizeAfterDays())->isoFormat('ll')
                : null,
        );
    }

    private static function anonymizeAfterDays(): int
    {
        $days = config('privacy.anonymize_deleted_users_after_days');

        return is_int($days) ? $days : 30;
    }
}
