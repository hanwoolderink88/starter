<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Services;

use App\Infrastructure\Collaboration\Data\DiffHunkData;
use App\Infrastructure\Collaboration\Data\DiffLineData;
use App\Infrastructure\Collaboration\Data\TextDiffData;
use App\Infrastructure\Collaboration\Enums\DiffLineType;
use InvalidArgumentException;

/**
 * A line diff between two texts, by the longest common subsequence of their
 * lines, grouped in hunks with context, like `diff -u`. No dependency; it
 * gives up on the subsequence (all removed, all added) for texts too large to
 * compare cheaply.
 */
class TextDiff
{
    public const int DEFAULT_CONTEXT_LINES = 3;

    /**
     * The largest LCS table (differing old lines × differing new lines) the
     * diff computes: about 4 MB of directions.
     */
    public const int MAX_TABLE_CELLS = 4_000_000;

    /**
     * @param  int  $contextLines  the unchanged lines shown around each change
     *
     * @throws InvalidArgumentException when a text is longer than a field may be
     */
    public function between(string $old, string $new, int $contextLines = self::DEFAULT_CONTEXT_LINES): TextDiffData
    {
        $maxLength = (int) config('collaboration.max_length');

        if (mb_strlen($old) > $maxLength || mb_strlen($new) > $maxLength) {
            throw new InvalidArgumentException(sprintf('A diff compares texts of at most %d characters.', $maxLength));
        }

        $lines = $this->lineOperations($this->lines($old), $this->lines($new));

        return new TextDiffData(
            hunks: $this->hunks($lines, max(0, $contextLines)),
            additions: count(array_filter($lines, fn (DiffLineData $line): bool => $line->type === DiffLineType::Added)),
            deletions: count(array_filter($lines, fn (DiffLineData $line): bool => $line->type === DiffLineType::Removed)),
        );
    }

    /**
     * An empty text has no lines; a text ending in a newline ends in an empty
     * line.
     *
     * @return list<string>
     */
    private function lines(string $text): array
    {
        if ($text === '') {
            return [];
        }

        return explode("\n", str_replace("\r\n", "\n", $text));
    }

    /**
     * Every line of both texts in order, marked as context, removed or added.
     *
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<DiffLineData>
     */
    private function lineOperations(array $old, array $new): array
    {
        $oldCount = count($old);
        $newCount = count($new);

        $prefix = 0;

        while ($prefix < $oldCount && $prefix < $newCount && $old[$prefix] === $new[$prefix]) {
            $prefix++;
        }

        $suffix = 0;

        while ($suffix < $oldCount - $prefix && $suffix < $newCount - $prefix && $old[$oldCount - 1 - $suffix] === $new[$newCount - 1 - $suffix]) {
            $suffix++;
        }

        $lines = [];

        for ($index = 0; $index < $prefix; $index++) {
            $lines[] = new DiffLineData(DiffLineType::Context, $old[$index], $index + 1, $index + 1);
        }

        $oldMiddle = array_slice($old, $prefix, $oldCount - $prefix - $suffix);
        $newMiddle = array_slice($new, $prefix, $newCount - $prefix - $suffix);

        foreach ($this->middleOperations($oldMiddle, $newMiddle) as [$type, $oldIndex, $newIndex]) {
            $lines[] = new DiffLineData(
                $type,
                $oldIndex !== null ? $oldMiddle[$oldIndex] : $newMiddle[(int) $newIndex],
                $oldIndex !== null ? $prefix + $oldIndex + 1 : null,
                $newIndex !== null ? $prefix + $newIndex + 1 : null,
            );
        }

        for ($index = $suffix; $index > 0; $index--) {
            $lines[] = new DiffLineData(DiffLineType::Context, $old[$oldCount - $index], $oldCount - $index + 1, $newCount - $index + 1);
        }

        return $lines;
    }

    /**
     * The operations that turn `$old` into `$new`, by the longest common
     * subsequence of their lines. Where both a removal and an addition fit,
     * the removal comes first, so a changed line reads as old, then new.
     *
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{DiffLineType, int|null, int|null}> type, old index, new index
     */
    private function middleOperations(array $old, array $new): array
    {
        $oldCount = count($old);
        $newCount = count($new);

        if ($oldCount === 0 || $newCount === 0 || $oldCount * $newCount > self::MAX_TABLE_CELLS) {
            return [
                ...array_map(fn (int $index): array => [DiffLineType::Removed, $index, null], array_keys($old)),
                ...array_map(fn (int $index): array => [DiffLineType::Added, null, $index], array_keys($new)),
            ];
        }

        $directions = $this->directions($old, $new);
        $operations = [];
        $oldIndex = 0;
        $newIndex = 0;

        while ($oldIndex < $oldCount && $newIndex < $newCount) {
            $direction = $directions[$oldIndex][$newIndex];

            if ($direction === 'd') {
                $operations[] = [DiffLineType::Context, $oldIndex++, $newIndex++];
            } elseif ($direction === 'u') {
                $operations[] = [DiffLineType::Removed, $oldIndex++, null];
            } else {
                $operations[] = [DiffLineType::Added, null, $newIndex++];
            }
        }

        while ($oldIndex < $oldCount) {
            $operations[] = [DiffLineType::Removed, $oldIndex++, null];
        }

        while ($newIndex < $newCount) {
            $operations[] = [DiffLineType::Added, null, $newIndex++];
        }

        return $operations;
    }

    /**
     * Per old line, a string with one direction per new line, computed from
     * the end: `d` (the lines match), `u` (skip the old line) or `l` (skip
     * the new line). Only two rows of LCS lengths are kept.
     *
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return array<int, string>
     */
    private function directions(array $old, array $new): array
    {
        $oldCount = count($old);
        $newCount = count($new);
        $directions = [];
        $below = array_fill(0, $newCount + 1, 0);

        for ($oldIndex = $oldCount - 1; $oldIndex >= 0; $oldIndex--) {
            $row = array_fill(0, $newCount + 1, 0);
            $rowDirections = str_repeat('l', $newCount);

            for ($newIndex = $newCount - 1; $newIndex >= 0; $newIndex--) {
                if ($old[$oldIndex] === $new[$newIndex]) {
                    $row[$newIndex] = $below[$newIndex + 1] + 1;
                    $rowDirections[$newIndex] = 'd';
                } elseif ($below[$newIndex] >= $row[$newIndex + 1]) {
                    $row[$newIndex] = $below[$newIndex];
                    $rowDirections[$newIndex] = 'u';
                } else {
                    $row[$newIndex] = $row[$newIndex + 1];
                }
            }

            $directions[$oldIndex] = $rowDirections;
            $below = $row;
        }

        return $directions;
    }

    /**
     * Group the changed lines into hunks with up to `$contextLines` unchanged
     * lines before and after; changes closer together than twice that share
     * a hunk.
     *
     * @param  list<DiffLineData>  $lines
     * @return list<DiffHunkData>
     */
    private function hunks(array $lines, int $contextLines): array
    {
        $count = count($lines);
        $oldBefore = [];
        $newBefore = [];
        $oldSeen = 0;
        $newSeen = 0;

        foreach ($lines as $index => $line) {
            $oldBefore[$index] = $oldSeen;
            $newBefore[$index] = $newSeen;
            $oldSeen += $line->oldNumber !== null ? 1 : 0;
            $newSeen += $line->newNumber !== null ? 1 : 0;
        }

        $hunks = [];
        $index = 0;

        while ($index < $count) {
            if ($lines[$index]->type === DiffLineType::Context) {
                $index++;

                continue;
            }

            $lastChange = $index;

            for ($cursor = $index + 1; $cursor < $count; $cursor++) {
                if ($lines[$cursor]->type !== DiffLineType::Context) {
                    $lastChange = $cursor;
                } elseif ($cursor - $lastChange > 2 * $contextLines) {
                    break;
                }
            }

            $start = max(0, $index - $contextLines);
            $end = min($count - 1, $lastChange + $contextLines);
            $hunkLines = array_slice($lines, $start, $end - $start + 1);
            $oldLines = count(array_filter($hunkLines, fn (DiffLineData $line): bool => $line->oldNumber !== null));
            $newLines = count(array_filter($hunkLines, fn (DiffLineData $line): bool => $line->newNumber !== null));

            $hunks[] = new DiffHunkData(
                oldStart: $oldBefore[$start] + ($oldLines > 0 ? 1 : 0),
                oldLines: $oldLines,
                newStart: $newBefore[$start] + ($newLines > 0 ? 1 : 0),
                newLines: $newLines,
                lines: $hunkLines,
            );

            $index = $end + 1;
        }

        return $hunks;
    }
}
