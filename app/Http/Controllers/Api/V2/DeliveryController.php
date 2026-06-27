<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Http\Resources\V2\DeliveryResource;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeliveryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user  = auth()->user();
        $query = Delivery::query()->with(['user', 'driver'])->orderBy('id');

        if ($user->role === 'admin') {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            $query->where('user_id', $user->id);
        }

        $deliveries = $query->cursorPaginate(20);

        return DeliveryResource::collection($deliveries);
    }

    public function show(Delivery $delivery): DeliveryResource
    {
        $this->authorize('view', $delivery);

        return new DeliveryResource($delivery->load(['user', 'driver', 'latestLog']));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_name'   => 'required|string|max:255',
            'recipient_phone'  => 'required|string|max:20',
            'pickup_address'   => 'required|string',
            'delivery_address' => 'required|string',
            'driver_id'        => 'nullable|exists:users,id',
            'scheduled_at'     => 'nullable|date',
        ]);

        $delivery = Delivery::create([
            ...$validated,
            'user_id'         => auth()->id(),
            'tenant_id'       => auth()->user()->tenant_id,
            'tracking_number' => strtoupper(\Illuminate\Support\Str::random(6)) . now()->format('His'),
        ]);

        return (new DeliveryResource($delivery->load(['user', 'driver'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(Request $request, Delivery $delivery): DeliveryResource
    {
        $this->authorize('update', $delivery);

        $delivery->update($request->validate([
            'status'    => 'sometimes|in:pending,assigned,picked_up,in_transit,delivered,failed,cancelled',
            'driver_id' => 'sometimes|nullable|exists:users,id',
        ]));

        return new DeliveryResource($delivery->load(['user', 'driver']));
    }

    public function destroy(Delivery $delivery): JsonResponse
    {
        $this->authorize('delete', $delivery);
        $delivery->delete();

        return response()->json(null, 204);
    }
}
