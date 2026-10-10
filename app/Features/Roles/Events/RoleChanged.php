<?php

declare(strict_types=1);

namespace App\Features\Roles\Events;

use App\Infrastructure\Broadcasting\Data\ResourceChangedData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoleChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ResourceChangedData $payload) {}

    /**
     * Broadcast on the collection channel (the role list) and the per-record
     * channel (anyone editing that one role).
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('roles'),
            new PrivateChannel('role.'.$this->payload->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'RoleChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload->toArray();
    }
}
