<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DeliveryResource;
use App\Models\Delivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DeliveryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit  = min((int) $request->query('limit', 20), 100);
        $cursor = $request->query('cursor');

        $user  = auth()->user();
        $query = Delivery::query()->orderBy('id');

        if ($user->role === 'admin') {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            $query->where('user_id', $user->id);
        }

        if ($cursor) {
            $decoded = $this->decodeCursor($cursor);
            if ($decoded === null) {
                abort(422, 'Invalid cursor');
            }
            $query->where('id', '>', $decoded);
        }

        $deliveries = $query->limit($limit + 1)->get();
        $hasMore    = $deliveries->count() > $limit;

        if ($hasMore) {
            $deliveries->pop();
        }

        $nextCursor = $hasMore
            ? $this->encodeCursor($deliveries->last()->id)
            : null;

        return DeliveryResource::collection($deliveries)
            ->additional([
                'meta' => [
                    'next_cursor' => $nextCursor,
                    'has_more'    => $hasMore,
                    'limit'       => $limit,
                ],
            ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_name'   => 'required|string|max:255',
            'recipient_phone'  => 'required|string|max:20',
            'pickup_address'   => 'required|string',
            'delivery_address' => 'required|string',
            'pickup_lat'       => 'nullable|numeric|between:-90,90',
            'pickup_lng'       => 'nullable|numeric|between:-180,180',
            'delivery_lat'     => 'nullable|numeric|between:-90,90',
            'delivery_lng'     => 'nullable|numeric|between:-180,180',
            'scheduled_at'     => 'nullable|date',
            'notes'            => 'nullable|string',
        ]);

        $delivery = Delivery::create([
            ...$validated,
            'user_id'         => auth()->id(),
            'tenant_id'       => auth()->user()->tenant_id,
            'status'          => 'pending',
            'tracking_number' => strtoupper(Str::random(6)) . now()->format('His'),
        ]);

        return (new DeliveryResource($delivery))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Delivery $delivery): DeliveryResource
    {
        $this->authorize('view', $delivery);

        return new DeliveryResource($delivery->load('latestLog'));
    }

    public function update(Request $request, Delivery $delivery): DeliveryResource
    {
        $this->authorize('update', $delivery);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,assigned,picked_up,in_transit,delivered,failed,cancelled',
            'notes'  => 'nullable|string',
        ]);

        $delivery->update($validated);

        return new DeliveryResource($delivery);
    }

    public function destroy(Delivery $delivery): JsonResponse
    {
        $this->authorize('delete', $delivery);
        $delivery->delete();

        return response()->json(null, 204);
    }

    private function encodeCursor(int $id): string
    {
        $payload = base64_encode($id);
        $sig     = hash_hmac('sha256', $payload, config('app.key'));

        return $payload . '.' . substr($sig, 0, 16);
    }

    private function decodeCursor(string $cursor): ?int
    {
        $parts = explode('.', $cursor, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $sig] = $parts;
        $expected = substr(hash_hmac('sha256', $payload, config('app.key')), 0, 16);

        if (! hash_equals($expected, $sig)) {
            return null;
        }

        return (int) base64_decode($payload);
    }
}
