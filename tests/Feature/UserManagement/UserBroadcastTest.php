<?php

use App\Broadcasting\Data\ResourceChangedData;
use App\Broadcasting\Enums\ResourceAction;
use App\Features\UserManagement\Enums\Role;
use App\Features\UserManagement\Events\UserChanged;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    seedUserRolesAndPermissions();
});

test('creating a user broadcasts UserChanged with the actor', function () {
    Event::fake([UserChanged::class]);
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'newuser@example.com',
        'role' => Role::User->value,
    ]);

    Event::assertDispatched(
        UserChanged::class,
        fn (UserChanged $event) => $event->payload->action === ResourceAction::Created
            && $event->payload->actorId === $admin->id,
    );
});

test('updating a user broadcasts UserChanged for that user', function () {
    Event::fake([UserChanged::class]);
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)->put(route('users.update', $user), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'role' => Role::User->value,
    ]);

    Event::assertDispatched(
        UserChanged::class,
        fn (UserChanged $event) => $event->payload->action === ResourceAction::Updated
            && $event->payload->id === $user->id,
    );
});

test('deleting a user broadcasts UserChanged for that user', function () {
    Event::fake([UserChanged::class]);
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)->delete(route('users.destroy', $user));

    Event::assertDispatched(
        UserChanged::class,
        fn (UserChanged $event) => $event->payload->action === ResourceAction::Deleted
            && $event->payload->id === $user->id,
    );
});

test('UserChanged broadcasts a thin signal on both channels', function () {
    $event = new UserChanged(new ResourceChangedData(
        action: ResourceAction::Updated,
        id: 5,
        label: 'Jane Doe',
        actorId: 1,
        actorName: 'Admin',
    ));

    $channelNames = array_map(
        fn (PrivateChannel $channel) => $channel->name,
        $event->broadcastOn(),
    );

    expect($channelNames)->toContain('private-users')
        ->toContain('private-user.5');
    expect($event->broadcastAs())->toBe('UserChanged');
    expect($event->broadcastWith())->toMatchArray([
        'action' => 'updated',
        'id' => 5,
        'label' => 'Jane Doe',
        'actorId' => 1,
        'actorName' => 'Admin',
    ]);
});
