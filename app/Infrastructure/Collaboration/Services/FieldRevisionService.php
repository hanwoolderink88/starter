<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Services;

use App\Infrastructure\Activity\Data\ChangeContext;
use App\Infrastructure\Collaboration\Models\FieldRevision;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * The history of long text fields: the full text per revision, with the
 * author of the session as a snapshot.
 */
class FieldRevisionService
{
    /**
     * Add the next revision. Called inside the transaction that locked the
     * record's row, so two sessions closing at once get consecutive numbers;
     * the unique index is the last guard.
     *
     * @param  CarbonImmutable|null  $startedAt  when the editing session began
     * @param  int|null  $restoredFromNumber  the revision whose text was restored
     */
    public function append(Model $model, string $field, string $content, ChangeContext $context, ?CarbonImmutable $startedAt, ?int $restoredFromNumber = null): FieldRevision
    {
        return FieldRevision::query()->create([
            'editable_type' => $model->getMorphClass(),
            'editable_id' => $model->getKey(),
            'field' => $field,
            'number' => $this->latestNumber($model, $field) + 1,
            'content' => $content,
            'author_id' => $context->actor?->id,
            'author_name' => $context->causerName(),
            'impersonator_id' => $context->impersonator?->id,
            'impersonator_name' => $context->impersonator?->name,
            'source' => $context->source,
            'restored_from_number' => $restoredFromNumber,
            'started_at' => $startedAt,
        ]);
    }

    public function latest(Model $model, string $field): ?FieldRevision
    {
        return $this->query($model, $field)->orderByDesc('number')->first();
    }

    public function find(Model $model, string $field, int $number): ?FieldRevision
    {
        return $this->query($model, $field)->where('number', $number)->first();
    }

    /**
     * @return Collection<int, FieldRevision> newest first
     */
    public function all(Model $model, string $field): Collection
    {
        return $this->query($model, $field)->orderByDesc('number')->get();
    }

    /**
     * @return array<string, int> the number of revisions per field
     */
    public function counts(Model $model): array
    {
        /** @var array<string, int> $counts */
        $counts = FieldRevision::query()
            ->where('editable_type', $model->getMorphClass())
            ->where('editable_id', $model->getKey())
            ->groupBy('field')
            ->selectRaw('field, count(*) as aggregate')
            ->pluck('aggregate', 'field')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        return $counts;
    }

    private function latestNumber(Model $model, string $field): int
    {
        return (int) $this->query($model, $field)->max('number');
    }

    /**
     * @return Builder<FieldRevision>
     */
    private function query(Model $model, string $field): Builder
    {
        return FieldRevision::query()
            ->where('editable_type', $model->getMorphClass())
            ->where('editable_id', $model->getKey())
            ->where('field', $field);
    }
}
