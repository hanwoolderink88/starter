<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * A save or heartbeat from a tab that does not hold the field (any more): its
 * session expired or was taken over after it expired. A 409; the editor
 * claims again or shows a conflict.
 */
class FieldNotHeldException extends RuntimeException
{
    public function render(): JsonResponse
    {
        return response()->json(['message' => __('collaboration.not_held')], 409);
    }
}
