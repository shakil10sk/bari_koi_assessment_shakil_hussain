<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeliveryRouteResource;
use App\Models\DeliveryRoute;
use App\Services\TenantRouteCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryRouteController extends Controller
{
    public function __construct(private readonly TenantRouteCache $cache) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $routes = $this->cache->getRoutes($tenantId);

        return response()->json([
            'data' => $routes,
            'meta' => ['tenant_id' => $tenantId, 'cached' => true],
        ]);
    }

    public function store(Request $request): DeliveryRouteResource
    {
        $tenantId = $request->user()->tenant_id;

        $route = DeliveryRoute::create([
            ...$request->validate([
                'name'        => 'required|string|max:255',
                'description' => 'nullable|string',
                'waypoints'   => 'required|array|min:2',
                'waypoints.*.lat'     => 'required|numeric|between:-90,90',
                'waypoints.*.lng'     => 'required|numeric|between:-180,180',
                'waypoints.*.address' => 'required|string',
            ]),
            'tenant_id' => $tenantId,
        ]);

        $this->cache->invalidate($tenantId);

        return new DeliveryRouteResource($route);
    }

    public function show(DeliveryRoute $deliveryRoute): DeliveryRouteResource
    {
        return new DeliveryRouteResource($deliveryRoute);
    }

    public function update(Request $request, DeliveryRoute $deliveryRoute): DeliveryRouteResource
    {
        $deliveryRoute->update($request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'waypoints'   => 'sometimes|array|min:2',
            'is_active'   => 'sometimes|boolean',
        ]));

        $this->cache->invalidate($deliveryRoute->tenant_id);

        return new DeliveryRouteResource($deliveryRoute);
    }

    public function destroy(DeliveryRoute $deliveryRoute): JsonResponse
    {
        $tenantId = $deliveryRoute->tenant_id;
        $deliveryRoute->delete();
        $this->cache->invalidate($tenantId);

        return response()->json(null, 204);
    }
}
