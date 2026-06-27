<?php

namespace App\Events;

use App\Models\ImportJob;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryImportStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly ImportJob $importJob) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->importJob->user_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'import.started';
    }

    public function broadcastWith(): array
    {
        return [
            'import_job_id' => $this->importJob->id,
            'filename'      => $this->importJob->filename,
            'status'        => $this->importJob->status,
        ];
    }
}
