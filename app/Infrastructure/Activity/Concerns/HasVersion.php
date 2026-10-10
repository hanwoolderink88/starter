<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Concerns;

use App\Infrastructure\Activity\Exceptions\StaleVersionException;
use Illuminate\Database\Eloquent\Model;

/**
 * Optimistic locking on an integer `version` column.
 *
 * A form or tool sends the version it was based on; `updateAtVersion()` checks
 * and saves in one `UPDATE … WHERE version = ?`, so two saves within the same
 * second still conflict. Services call it; the model's table needs an integer
 * `version` column and the conflict messages under `{table}.conflict.changed_by`
 * and `{table}.conflict.changed`.
 *
 * @mixin Model
 */
trait HasVersion
{
    public function initializeHasVersion(): void
    {
        $this->mergeCasts(['version' => 'integer']);
    }

    /**
     * Save the attributes only if the record is still at the expected version,
     * and raise the version by one. The attributes go through `fill()`, so
     * casts and mutators apply. Without changed attributes the version is still
     * raised, because a change outside the attributes (such as the roles or
     * permissions) may belong to the same save. Like `save()`, the update
     * ignores global scopes, so an inactive role can still be saved.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws StaleVersionException when someone saved the record in the meantime
     */
    public function updateAtVersion(int $expectedVersion, array $attributes = []): void
    {
        $this->fill($attributes);

        $affected = $this->newModelQuery()
            ->withoutGlobalScopes()
            ->whereKey($this->getKey())
            ->where('version', $expectedVersion)
            ->update([...$this->getDirty(), 'version' => $expectedVersion + 1]);

        $this->refresh();

        if ($affected === 0) {
            throw StaleVersionException::for($this, $this->getTable().'.conflict');
        }
    }

    /**
     * Raise the version for a change that is not checked against one, such as
     * a user editing their own profile, so a save based on the version before
     * it is refused.
     */
    public function bumpVersion(): void
    {
        $this->newModelQuery()->withoutGlobalScopes()->whereKey($this->getKey())->increment('version');

        $this->setAttribute('version', (int) $this->getAttribute('version') + 1);
        $this->syncOriginalAttribute('version');
    }
}
