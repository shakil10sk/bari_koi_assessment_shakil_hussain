<?php

namespace App\Services;

use App\Models\DeliveryRoute;
use Illuminate\Support\Facades\Cache;

class TenantRouteCache
{
    private const TTL_SECONDS = 300;

    public function getRoutes(int $tenantId): array
    {
        return Cache::remember(
            $this->cacheKey($tenantId),
            self::TTL_SECONDS,
            fn () => DeliveryRoute::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->get()
                ->toArray()
        );
    }

    public function invalidate(int $tenantId): void
    {
        Cache::forget($this->cacheKey($tenantId));
    }

    private function cacheKey(int $tenantId): string
    {
        return "tenant:{$tenantId}:routes";
    }
}
