<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Services;

use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Persistence of field locks. Every change to a lock happens in a transaction
 * that first locks the record's row with `lockRecord()`, so claims, saves,
 * releases and the closing of expired sessions on one record run one after
 * the other. The unique index on (record, field) is the last guard.
 */
class FieldLockService
{
    /**
     * Lock the record's row for the rest of the transaction, refresh the
     * model from it, and return the field's lock, expired or not.
     */
    public function lockRecord(Model $model, string $field): ?FieldLock
    {
        $current = $model->newQueryWithoutScopes()->whereKey($model->getKey())->lockForUpdate()->firstOrFail();
        $model->setRawAttributes($current->getAttributes(), true);

        return $this->find($model, $field);
    }

    public function find(Model $model, string $field): ?FieldLock
    {
        return FieldLock::query()
            ->where('editable_type', $model->getMorphClass())
            ->where('editable_id', $model->getKey())
            ->where('field', $field)
            ->first();
    }

    /**
     * The locks of the record that have not expired, by field.
     *
     * @return Collection<string, FieldLock>
     */
    public function activeFor(Model $model): Collection
    {
        return FieldLock::query()
            ->with(['user', 'impersonator'])
            ->where('editable_type', $model->getMorphClass())
            ->where('editable_id', $model->getKey())
            ->where('expires_at', '>', CarbonImmutable::now())
            ->get()
            ->keyBy('field');
    }

    /**
     * A new lock, claimed now: it expires after the connection grace period,
     * unless a heartbeat or a save comes first.
     */
    public function create(Model $model, string $field, User $user, ActivitySource $source, ?string $clientId, ?User $impersonator, ?string $baseValue): FieldLock
    {
        $now = CarbonImmutable::now();

        return FieldLock::query()->create([
            'editable_type' => $model->getMorphClass(),
            'editable_id' => $model->getKey(),
            'field' => $field,
            'user_id' => $user->id,
            'impersonator_id' => $impersonator?->id,
            'source' => $source,
            'client_id' => $clientId,
            'base_value' => $baseValue,
            'claimed_at' => $now,
            'heartbeat_at' => $now,
            'last_edit_at' => $now,
            'expires_at' => $this->expiresAt($now, $now),
        ]);
    }

    /**
     * The editor is still open: move the connection limit.
     */
    public function heartbeat(FieldLock $lock): FieldLock
    {
        return $this->touch($lock, CarbonImmutable::now(), $lock->last_edit_at);
    }

    /**
     * The holder saved: move the idle limit. A save also proves the
     * connection, so it counts as a heartbeat.
     */
    public function recordEdit(FieldLock $lock): FieldLock
    {
        $now = CarbonImmutable::now();

        return $this->touch($lock, $now, $now);
    }

    public function delete(FieldLock $lock): void
    {
        FieldLock::query()->whereKey($lock->id)->delete();
    }

    /**
     * Every lock whose session has expired and still needs closing.
     *
     * @return Collection<int, FieldLock>
     */
    public function expired(): Collection
    {
        return FieldLock::query()
            ->where('expires_at', '<=', CarbonImmutable::now())
            ->orderBy('expires_at')
            ->get();
    }

    private function touch(FieldLock $lock, CarbonImmutable $heartbeatAt, CarbonImmutable $lastEditAt): FieldLock
    {
        $lock->update([
            'heartbeat_at' => $heartbeatAt,
            'last_edit_at' => $lastEditAt,
            'expires_at' => $this->expiresAt($heartbeatAt, $lastEditAt),
        ]);

        return $lock;
    }

    /**
     * The earlier of the connection limit and the idle limit.
     */
    private function expiresAt(CarbonImmutable $heartbeatAt, CarbonImmutable $lastEditAt): CarbonImmutable
    {
        $connectionLimit = $heartbeatAt->addSeconds(config()->integer('collaboration.connection_grace_seconds'));
        $idleLimit = $lastEditAt->addSeconds(config()->integer('collaboration.idle_seconds'));

        return $connectionLimit->min($idleLimit);
    }
}
