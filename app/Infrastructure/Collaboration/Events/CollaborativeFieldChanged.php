<?php

declare(strict_types=1);

namespace App\Infrastructure\Collaboration\Events;

use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A field of a record was claimed, saved or released. The payload's `id` is
 * the record and its `label` the field; the value itself is re-fetched
 * through Inertia, never sent over the socket.
 */
class CollaborativeFieldChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<string>  $channels  from CollaborativeModel::collaborationChannels()
     */
    public function __construct(
        public ResourceChangedData $payload,
        public array $channels,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_map(fn (string $channel): PrivateChannel => new PrivateChannel($channel), $this->channels);
    }

    public function broadcastAs(): string
    {
        return 'CollaborativeFieldChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload->toArray();
    }
}
