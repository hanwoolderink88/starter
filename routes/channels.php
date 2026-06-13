<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Co-working channels for the UserManagement feature. Authorized against the
// same policy abilities the corresponding pages use.
Broadcast::channel('users', fn (User $user): bool => $user->can('viewAny', User::class));

Broadcast::channel('user.{id}', function (User $user, int $id): bool {
    $target = User::find($id);

    return $target !== null && $user->can('view', $target);
});

// Presence channel powering "who else is viewing this user" awareness.
Broadcast::channel('viewing.user.{id}', function (User $user, int $id): ?array {
    $target = User::find($id);

    if ($target === null || ! $user->can('view', $target)) {
        return null;
    }

    return ['id' => $user->id, 'name' => $user->name];
});
