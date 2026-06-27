<?php

namespace App\Console\Commands;

use App\Jobs\SendDeliveryNotification;
use App\Models\Delivery;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('deliveries:notify-all')]
#[Description('Dispatch notifications for all pending deliveries without exhausting memory')]
class ProcessDeliveryNotifications extends Command
{
    public function handle(): int
    {
        $count = 0;

        Delivery::where('status', 'pending')
            ->cursor()
            ->each(function (Delivery $delivery) use (&$count) {
                SendDeliveryNotification::dispatch($delivery);
                $count++;
            });

        $this->info("Dispatched notifications for {$count} deliveries.");

        return self::SUCCESS;
    }
}
