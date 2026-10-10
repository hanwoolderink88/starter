<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Contracts;

use App\Infrastructure\Collaboration\Data\CollaborativeField;
use Illuminate\Database\Eloquent\Model;

/**
 * A model whose fields people edit together: one person per field at a time,
 * saved as they type, and seen by everyone else at once.
 *
 * The values stay in the model's own columns. Editing needs the `update`
 * ability of the model's policy, and the model needs a morph alias.
 *
 * @mixin Model
 */
interface CollaborativeModel
{
    /**
     * The fields that are edited together, by column name.
     *
     * @return array<string, CollaborativeField>
     */
    public function collaborativeFields(): array;

    /**
     * The private channels that hear about changes to these fields, such as
     * the record's own channel and its list's.
     *
     * @return list<string>
     */
    public function collaborationChannels(): array;
}
