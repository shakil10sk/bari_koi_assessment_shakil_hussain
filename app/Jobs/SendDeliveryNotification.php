<?php

namespace App\Jobs;

use App\Models\Delivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendDeliveryNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $maxExceptions = 3;

    public function __construct(public readonly Delivery $delivery) {}

    public function backoff(): array
    {
        return [60, 120, 240, 480, 960];
    }

    public function handle(): void
    {
        Http::timeout(10)->post(config('services.notification.url'), [
            'tracking_number' => $this->delivery->tracking_number,
            'status'          => $this->delivery->status,
            'recipient_phone' => $this->delivery->recipient_phone,
        ])->throw();
    }

    public function failed(Throwable $exception): void
    {
        Log::critical('SendDeliveryNotification permanently failed', [
            'delivery_id'    => $this->delivery->id,
            'tracking_number' => $this->delivery->tracking_number,
            'error'          => $exception->getMessage(),
        ]);

        Http::post(config('services.alert.webhook'), [
            'text' => "ALERT: Delivery notification failed for #{$this->delivery->tracking_number}: {$exception->getMessage()}",
        ])->throw();
    }
}
