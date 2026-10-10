<?php

declare(strict_types=1);

namespace App\Infrastructure\Activity\Models;

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Override;

/**
 * One entry in the append-only activity log. Written only through
 * `ActivityRecorder`; a database trigger refuses every update and delete.
 *
 * @property int $id
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int|null $causer_id
 * @property string $causer_name
 * @property int|null $impersonator_id
 * @property string|null $impersonator_name
 * @property ActivitySource $source
 * @property ActivityEvent $event
 * @property array<string, mixed> $changes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property CarbonImmutable $created_at
 * @property-read Model|null $subject
 */
#[UseFactory(ActivityFactory::class)]
class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    #[Override]
    protected $fillable = [
        'subject_type',
        'subject_id',
        'causer_id',
        'causer_name',
        'impersonator_id',
        'impersonator_name',
        'source',
        'event',
        'changes',
        'ip_address',
        'user_agent',
    ];

    /**
     * The record the entry is about, whatever state it is in: a global scope
     * such as the one that hides inactive roles must not hide it from the log.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo()->withoutGlobalScopes();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id')->withTrashed();
    }

    /**
     * The entries about one subject, newest first.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function forSubject(Builder $query, Model $subject): void
    {
        $query->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->latest()
            ->latest('id');
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'source' => ActivitySource::class,
            'event' => ActivityEvent::class,
            'changes' => 'array',
        ];
    }
}
