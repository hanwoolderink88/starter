<?php

use App\Features\UserManagement\Events\UserChanged;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use App\Infrastructure\Broadcasting\Enums\ResourceAction;
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
    ]);

    Event::assertDispatched(
        UserChanged::class,
        fn (UserChanged $event) => $event->payload->action === ResourceAction::Created
            && $event->payload->actorId === $admin->id,
    );
});

test('a web request threads its X-Client-Id header into the broadcast origin', function () {
    Event::fake([UserChanged::class]);
    $admin = createAdmin();

    $this->actingAs($admin)
        ->withHeader('X-Client-Id', 'tab-abc')
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'newuser@example.com',
        ]);

    Event::assertDispatched(
        UserChanged::class,
        fn (UserChanged $event) => $event->payload->origin === 'tab-abc',
    );
});

test('a request with no client id broadcasts a null origin so every browser updates', function () {
    Event::fake([UserChanged::class]);
    $admin = createAdmin();

    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'Headless User',
        'email' => 'headless@example.com',
    ]);

    Event::assertDispatched(
        UserChanged::class,
        fn (UserChanged $event) => $event->payload->origin === null,
    );
});

test('updating a user broadcasts UserChanged for that user', function () {
    Event::fake([UserChanged::class]);
    $admin = createAdmin();
    $user = createRegularUser();

    $this->actingAs($admin)->put(route('users.update', $user), ['version' => $user->fresh()->version,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
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
        source: ActivitySource::Web,
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
        'source' => 'web',
        'origin' => null,
        'impersonatorName' => null,
    ]);
});
