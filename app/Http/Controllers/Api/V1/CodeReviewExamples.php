<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateDeliveryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeliveryRequest;
use App\Http\Resources\V1\DeliveryResource;
use App\Models\Delivery;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Code review reference — shows three common patterns we fixed during refactoring.
 * These are NOT active routes; kept here so reviewers can see before/after in one file.
 */

// ── ISSUE 1: N+1 writes inside a loop ────────────────────────────────────────
//
// Before:
//
//   public function store(Request $request)
//   {
//       $delivery = Delivery::create($request->only(['user_id','tracking_number','status']));
//       foreach ($request->waypoints as $waypoint) {
//           Waypoint::create(['delivery_id' => $delivery->id, 'address' => $waypoint['address']]);
//       }
//       return response()->json($delivery);
//   }
//
// Problems:
//   - one INSERT per waypoint (100 waypoints = 101 queries)
//   - no transaction → partial writes if the loop crashes
//
// After (see CreateDeliveryAction + StoreDeliveryRequest):

class CodeReviewExamples extends Controller
{
    public function store(StoreDeliveryRequest $request, CreateDeliveryAction $action): JsonResponse
    {
        $delivery = $action->execute(array_merge(
            $request->validated(),
            ['user_id' => auth()->id(), 'tenant_id' => auth()->user()->tenant_id]
        ));

        return (new DeliveryResource($delivery))->response()->setStatusCode(201);
    }
}

// ── ISSUE 2: DB hit on every request for tenant resolution ───────────────────
//
// Before (BuggyTenantMiddleware):
//
//   $tenant = Tenant::where('api_key', $request->header('X-Tenant-Key'))->firstOrFail();
//
// Under load this saturates the DB connection pool.
// Fixed in TenantMiddleware.php — tenant cached for 5 minutes by api_key.

// ── ISSUE 3: Delivery::all() on a 100k-row table ────────────────────────────
//
// Before:
//
//   public function report()
//   {
//       return response()->json(Delivery::all());
//   }
//
// PHP OOM at ~10k rows; client timeout well before that.
// Fixed options:
//   a) paginate(100) for synchronous queries
//   b) 202 + background job for large exports (see ReportController / ExportDeliveriesToCsv)

// ── PRODUCTION 500s UNDER LOAD — diagnostic steps ───────────────────────────
//
// 1. storage/logs/laravel.log — look for "Too many connections", "Deadlock"
// 2. pg_stat_activity — spot idle-in-transaction connections eating the pool
// 3. k6 / ab load test while Horizon is running — watch queue backlog spike
// 4. EXPLAIN ANALYZE on slow queries during load
// 5. Common root causes in Laravel:
//    - N+1 inside a loop creating per-request transactions → deadlocks
//    - Cache stampede (many misses at once) → use Cache::lock() for atomic compute
//    - SESSION_DRIVER=database under high traffic → switch to redis
//    - Horizon worker memory growth → set --max-jobs on workers
