<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'tracking_number'  => $this->tracking_number,
            'status'           => $this->status,
            'user_id'          => $this->user_id,
            'driver_id'        => $this->driver_id,
            'recipient_name'   => $this->recipient_name,
            'recipient_phone'  => $this->recipient_phone,
            'pickup_address'   => $this->pickup_address,
            'delivery_address' => $this->delivery_address,
            'scheduled_at'     => $this->scheduled_at?->toISOString(),
            'delivered_at'     => $this->delivered_at?->toISOString(),
            'created_at'       => $this->created_at->toISOString(),
            'updated_at'       => $this->updated_at->toISOString(),
        ];
    }
}
