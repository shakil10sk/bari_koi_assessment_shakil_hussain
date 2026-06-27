<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'delivery_id' => $this->delivery_id,
            'from_status' => $this->from_status,
            'to_status'   => $this->to_status,
            'event'       => $this->event,
            'notes'       => $this->notes,
            'metadata'    => $this->metadata,
            'location'    => $this->lat ? ['lat' => $this->lat, 'lng' => $this->lng] : null,
            'actor'       => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'name' => $this->user->name,
            ]),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
