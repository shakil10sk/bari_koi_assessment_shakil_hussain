<?php

namespace App\Actions;

use App\Models\Delivery;
use App\Services\TenantRouteCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateDeliveryAction
{
    public function __construct(private readonly TenantRouteCache $routeCache) {}

    public function execute(array $data): Delivery
    {
        return DB::transaction(function () use ($data) {
            $delivery = Delivery::create([
                ...$data,
                'tracking_number' => strtoupper(Str::random(6)) . now()->format('His'),
            ]);

            // Cache invalidation belongs here — close to the write that triggers it
            if (isset($data['tenant_id'])) {
                $this->routeCache->invalidate($data['tenant_id']);
            }

            return $delivery;
        });
    }
}
