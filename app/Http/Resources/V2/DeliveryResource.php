<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'tracking_number' => $this->tracking_number,
            'status'          => $this->status,
            'assigned_agent'  => $this->whenLoaded('driver', fn () => [
                'id'    => $this->driver->id,
                'name'  => $this->driver->name,
                'email' => $this->driver->email,
                'phone' => $this->driver->phone,
                'role'  => $this->driver->role,
            ]),
            'customer' => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone,
            ]),
            'pickup' => [
                'address' => $this->pickup_address,
                'lat'     => $this->pickup_lat,
                'lng'     => $this->pickup_lng,
            ],
            'destination' => [
                'address'      => $this->delivery_address,
                'lat'          => $this->delivery_lat,
                'lng'          => $this->delivery_lng,
                'recipient'    => $this->recipient_name,
                'phone'        => $this->recipient_phone,
            ],
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at'   => $this->created_at->toISOString(),
            'updated_at'   => $this->updated_at->toISOString(),
        ];
    }
}
