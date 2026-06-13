<?php

declare(strict_types=1);

namespace App\Features\UserManagement\Events;

use App\Broadcasting\Data\ResourceChangedData;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ResourceChangedData $payload) {}

    /**
     * Broadcast on both the collection channel (list/dashboard viewers) and the
     * per-record channel (anyone viewing/editing that one user).
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users'),
            new PrivateChannel('user.'.$this->payload->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload->toArray();
    }
}
