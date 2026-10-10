<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Data;

use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Models\User;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Who is editing a field right now, as the viewer sees it.
 */
#[TypeScript]
class FieldLockData extends Data
{
    public function __construct(
        public string $userName,
        public ?string $impersonatorName,
        public ActivitySource $source,
        /** The viewer holds it, in any tab. */
        public bool $isMine,
        /** The viewer holds it in the tab that asked. */
        public bool $isThisTab,
        public string $claimedAt,
    ) {}

    public static function fromLock(FieldLock $lock, User $viewer, ?string $clientId): self
    {
        $isMine = $lock->user_id === $viewer->id;

        return new self(
            userName: $lock->user->name,
            impersonatorName: $lock->impersonator?->name,
            source: $lock->source,
            isMine: $isMine,
            isThisTab: $isMine && $clientId !== null && $lock->client_id === $clientId,
            claimedAt: $lock->claimed_at->toISOString() ?? '',
        );
    }
}
