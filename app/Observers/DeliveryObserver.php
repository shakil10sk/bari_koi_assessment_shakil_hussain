<?php

namespace App\Observers;

use App\Events\DeliveryStatusChanged;
use App\Models\Delivery;
use App\Models\DeliveryLog;

class DeliveryObserver
{
    public function updating(Delivery $delivery): void
    {
        if (! $delivery->isDirty('status')) {
            return;
        }

        DeliveryLog::create([
            'delivery_id' => $delivery->id,
            'user_id'     => auth()->id(),
            'from_status' => $delivery->getOriginal('status'),
            'to_status'   => $delivery->status,
            'event'       => 'status_changed',
            'notes'       => "Status changed from {$delivery->getOriginal('status')} to {$delivery->status}",
        ]);

        DeliveryStatusChanged::dispatch($delivery, $delivery->getOriginal('status'));
    }
}
