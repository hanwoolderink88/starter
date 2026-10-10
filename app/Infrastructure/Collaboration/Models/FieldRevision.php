<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Models;

use App\Infrastructure\Activity\Enums\ActivitySource;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * One version of a long text field: the full text after an editing session,
 * or after a restore. Append-only; a trigger refuses updates and deletes.
 *
 * @property int $id
 * @property string $editable_type
 * @property int $editable_id
 * @property string $field
 * @property int $number
 * @property string $content
 * @property int|null $author_id
 * @property string $author_name
 * @property int|null $impersonator_id
 * @property string|null $impersonator_name
 * @property ActivitySource $source
 * @property int|null $restored_from_number
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable $created_at
 */
class FieldRevision extends Model
{
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'editable_type',
        'editable_id',
        'field',
        'number',
        'content',
        'author_id',
        'author_name',
        'impersonator_id',
        'impersonator_name',
        'source',
        'restored_from_number',
        'started_at',
    ];

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'source' => ActivitySource::class,
            'started_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
