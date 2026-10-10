<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Models;

use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;

/**
 * Someone editing one field of a record right now. Expired locks count as
 * free everywhere, also while their row still exists.
 *
 * @property int $id
 * @property string $editable_type
 * @property int $editable_id
 * @property string $field
 * @property int $user_id
 * @property int|null $impersonator_id
 * @property ActivitySource $source
 * @property string|null $client_id
 * @property string|null $base_value
 * @property CarbonImmutable $claimed_at
 * @property CarbonImmutable $heartbeat_at
 * @property CarbonImmutable $last_edit_at
 * @property CarbonImmutable $expires_at
 * @property-read User $user
 * @property-read User|null $impersonator
 */
class FieldLock extends Model
{
    #[Override]
    public $timestamps = false;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'editable_type',
        'editable_id',
        'field',
        'user_id',
        'impersonator_id',
        'source',
        'client_id',
        'base_value',
        'claimed_at',
        'heartbeat_at',
        'last_edit_at',
        'expires_at',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function impersonator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'impersonator_id')->withTrashed();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->lessThanOrEqualTo(CarbonImmutable::now());
    }

    /**
     * Whether this user holds the field in this browser tab. A tool (no tab)
     * only matches a lock without one.
     */
    public function isHeldBy(?User $user, ?string $clientId): bool
    {
        return $user instanceof User && $this->user_id === $user->id && $this->client_id === $clientId;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'source' => ActivitySource::class,
            'claimed_at' => 'immutable_datetime',
            'heartbeat_at' => 'immutable_datetime',
            'last_edit_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
