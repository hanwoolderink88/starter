<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Exceptions;

use App\Infrastructure\Collaboration\Models\FieldLock;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Someone else, or the same user in another tab, holds the field. A 409 that
 * names who.
 */
class FieldLockedException extends RuntimeException
{
    public static function heldBy(FieldLock $lock, ?User $viewer): self
    {
        $message = $viewer instanceof User && $lock->user_id === $viewer->id
            ? __('collaboration.locked_other_tab')
            : __('collaboration.locked_by', ['name' => $lock->user->name]);

        return new self($message);
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 409);
    }
}
