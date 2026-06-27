<?php

namespace App\Events;

use App\Models\Delivery;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Delivery $delivery,
        public readonly ?string $previousStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("driver.{$this->delivery->driver_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'delivery.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'delivery_id'     => $this->delivery->id,
            'tracking_number' => $this->delivery->tracking_number,
            'previous_status' => $this->previousStatus,
            'new_status'      => $this->delivery->status,
            'updated_at'      => $this->delivery->updated_at->toISOString(),
        ];
    }
}
