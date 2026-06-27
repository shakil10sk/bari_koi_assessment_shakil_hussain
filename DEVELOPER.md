# Developer Reference — Laravel Assessment (100 Marks)

This document explains every design decision, the "why" behind each implementation, and maps code to the specific assessment part it satisfies.

---

## Part 01 · Database & Eloquent Basics (10 marks)

### 1.1 — Schema Design

**File:** `database/migrations/`

Three core tables:

| Table | Soft Deletes | Rationale |
|-------|-------------|-----------|
| `tenants` | Yes | Tenants may be offboarded; history must be retained |
| `users` | Yes | User accounts must be recoverable; compliance |
| `deliveries` | Yes | Delivery history is auditable; never hard-delete |
| `delivery_logs` | No | Append-only audit log; deletion is never valid |

**Key schema decisions:**

- `deliveries.status` is an `ENUM` column, not a string. This enforces valid values at the DB layer and is more efficient than a `CHECK` constraint on a VARCHAR.
- Coordinates use `DECIMAL(10,7)` — 7 decimal places gives ~1 cm precision, matching GPS accuracy.
- `delivery_logs.from_status` is nullable to handle the initial `pending` creation log.
- All foreign keys cascade or nullify on delete — no orphaned rows.

### 1.2 — Relationships & Querying

**File:** `app/Models/Delivery.php` — `forUserWithLogSummary()`

```php
Delivery::query()
    ->where('user_id', $userId)
    ->withCount('logs')
    ->with(['latestLog'])
    ->latest();
```

This executes **3 queries** total (main + count + latest log), regardless of how many deliveries are returned. The `HasOne::latestOfMany()` relationship uses a subquery rather than loading all logs and picking the last one in PHP.

---

## Part 02 · Query Optimization (10 marks)

### 2.1 — Index Strategy

**File:** `database/migrations/2026_06_27_050543_add_indexes_to_deliveries_table.php`

```sql
-- Primary filter index: covers all three WHERE conditions in order of selectivity
CREATE INDEX idx_deliveries_user_status_date
    ON deliveries (user_id, status, created_at);
```

**Why this order?**

1. `user_id` first — highest selectivity (filters to one user's rows)
2. `status` second — medium selectivity (filters by state)
3. `created_at` last — used for range scan on the already-filtered result set

PostgreSQL can use this index for:
- `WHERE user_id = ? AND status = ? AND created_at BETWEEN ? AND ?` ✓
- `WHERE user_id = ? AND status = ?` ✓
- `WHERE user_id = ?` ✓

An index on `(status, created_at)` alone would scan far more rows. A separate index per column would force a bitmap index scan merge, which is slower on large tables.

### 2.2 — Diagnosing a Slow Query

**File:** `app/Services/QueryDiagnosticService.php`

**Step-by-step process:**

1. **Get the query plan:**
   ```sql
   EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT) <slow_query>;
   ```
   Look for: `Seq Scan` (missing index), `Nested Loop` with high rows, `Sort` with no index.

2. **Identify blocking/waiting queries:**
   ```sql
   SELECT pid, now() - query_start AS duration, query, state, wait_event
   FROM pg_stat_activity
   WHERE state = 'active' AND now() - query_start > INTERVAL '5 seconds';
   ```

3. **Check index usage:**
   ```sql
   SELECT * FROM pg_stat_user_indexes WHERE relname = 'deliveries';
   ```

4. **Common root causes found:**
   - **Correlated subquery in LEFT JOIN** — runs once per row; replace with `DISTINCT ON` + `ORDER BY (delivery_id, created_at DESC)` or a `LATERAL` join
   - **Missing composite index on `delivery_logs(delivery_id, created_at DESC)`** — converts the subquery from a seq scan to an index scan
   - **Stale planner statistics** — run `VACUUM ANALYZE deliveries;` after large bulk inserts
   - **N+1 in Eloquent** — use `with()` eager loading; detected via Laravel Telescope

---

## Part 03 · Handling Large Datasets (12 marks)

### 3.1 — Memory-safe Data Processing

**File:** `app/Console/Commands/ProcessDeliveryNotifications.php`

**The bug in the original code:**
```php
// WRONG — loads all 100k Eloquent models into PHP memory at once
$deliveries = Delivery::where('status', 'pending')->get();
```

**The fix:**
```php
// RIGHT — uses a server-side DB cursor; constant ~10MB memory regardless of row count
Delivery::where('status', 'pending')->cursor()->each(function (Delivery $d) {
    SendDeliveryNotification::dispatch($d);
});
```

`cursor()` uses PDO's `PDO::FETCH_OBJ` in forward-only mode — the DB returns one row at a time. Memory stays flat because only one Eloquent model exists at a time (the previous one is garbage-collected before the next arrives).

Alternative: `chunk(500, fn($batch) => ...)` — processes 500 rows per query, trades memory for query count. Use `chunk` when you need the whole batch (e.g., bulk insert), use `cursor` for single-record dispatch.

### 3.2 — Cursor-based Pagination

**File:** `app/Http/Controllers/Api/V1/DeliveryController.php` — `index()`

**Why offset pagination breaks at page 200:**
```sql
-- PostgreSQL must scan and discard 4,000 rows before returning 20
SELECT * FROM deliveries LIMIT 20 OFFSET 4000;
```
Each page gets slower as the offset grows. At 100k rows and 20 per page, page 5,000 is unusably slow.

**Cursor approach:**
```sql
-- Always fast — uses the PK index; no skipped rows
SELECT * FROM deliveries WHERE id > :last_seen_id ORDER BY id LIMIT 21;
```

**HMAC signing** prevents clients from guessing or forging cursors (e.g., skipping to `id=1` to scrape data). The signature is verified with `hash_equals()` to prevent timing attacks.

### 3.3 — Deferring Long-running Work

**File:** `app/Http/Controllers/Api/V1/ReportController.php`, `app/Jobs/GenerateDeliveryReport.php`

```
Client → POST /reports/weekly → 202 Accepted + {report_key}
                                       ↓
                             Queue → GenerateDeliveryReport
                                       ↓
                             Redis Cache: "report:{key}" = {data}
                                       ↓
Client → GET /reports/{key}/status → 200 {data} when ready, 202 pending
```

The HTTP request always returns in <100ms. The client polls until `status === 'ready'`. Cached results expire after 1 hour.

---

## Part 04 · Export Pipelines (10 marks)

### 4.1 — Large CSV Export

**File:** `app/Jobs/ExportDeliveriesToCsv.php`

**Memory-safe technique:** `chunk(500)` — fetches 500 rows per SQL query, writes to a temp file via `League\Csv\Writer`, then uploads to storage. At no point are all 50k rows in memory.

**Download URL expiry:** `Storage::temporaryUrl($path, now()->addHour())` returns a pre-signed URL valid for 1 hour. The URL reference is stored in Redis with the same 1-hour TTL, so both expire together.

### 4.2 — Dashboard Aggregation Query

**File:** `app/Jobs/GenerateDeliveryReport.php` — `buildWeeklyReport()`

```sql
SELECT
    date_trunc('week', created_at)  AS week,
    COUNT(*)                         AS total_deliveries,
    ROUND(100.0 * SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END)
          / NULLIF(COUNT(*), 0), 2) AS success_rate,
    ROUND(AVG(
        EXTRACT(EPOCH FROM (delivered_at - created_at)) / 3600
    )::numeric, 2)                   AS avg_delivery_hours
FROM deliveries
WHERE created_at >= NOW() - INTERVAL '3 months'
  AND deleted_at IS NULL
GROUP BY date_trunc('week', created_at)
ORDER BY week;
```

**Caching decision:** Yes, cache this result. Reasons:
- The query scans ~90 days of data — expensive on large tables.
- Dashboard data doesn't need to be realtime; 5-minute staleness is acceptable.
- `Cache::remember('dashboard:weekly', 300, fn() => ...)` is sufficient.
- Cache is invalidated when a delivery's status changes to `delivered` (hook into the observer if exact freshness is required).

---

## Part 05 · API Design (10 marks)

### 5.1 — Versioned API Resource

**Files:** `app/Http/Resources/V1/DeliveryResource.php`, `app/Http/Resources/V2/DeliveryResource.php`

V1 returns a flat structure:
```json
{ "user_id": 1, "driver_id": 3, "recipient_name": "..." }
```

V2 restructures user and driver as nested objects:
```json
{
  "assigned_agent": { "id": 3, "name": "...", "email": "...", "phone": "...", "role": "driver" },
  "customer":       { "id": 1, "name": "...", "email": "..." },
  "pickup":         { "address": "...", "lat": 23.79, "lng": 90.40 },
  "destination":    { "address": "...", "recipient": "...", "phone": "..." }
}
```

**Deprecation (Part 5.1):** `DeprecatedApiMiddleware` adds standard HTTP headers to every v1 response:
```
Deprecation: true
Sunset: Sat, 01 Jan 2027 00:00:00 GMT
Warning: 299 - "API v1 is deprecated. Please migrate to v2."
Link: <https://api.example.com/v2>; rel="successor-version"
```
These are RFC 8594-compliant headers. Well-behaved HTTP clients (SDKs, Guzzle middleware) can detect and warn about them automatically.

### 5.2 — Tenant Middleware & Rate Limiting

**File:** `app/Http/Middleware/TenantMiddleware.php`

**Efficiency under high traffic:** The middleware caches the tenant by its API key for 5 minutes. A cold request costs 1 DB query; subsequent requests within 5 minutes cost 0. Under 1,000 req/s, this avoids 299,999 unnecessary DB lookups per minute.

**Rate limiting** is configured in `AppServiceProvider::boot()`:
```php
RateLimiter::for('api', function (Request $request) {
    $tenant = app()->has('tenant') ? app('tenant') : null;
    if ($tenant) {
        return Limit::perMinute($tenant->rate_limit_per_minute)->by("tenant:{$tenant->id}");
    }
    return Limit::perMinute(30)->by($request->ip());
});
```

Plan limits stored in `tenants.rate_limit_per_minute`:
| Plan | Requests/minute |
|------|----------------|
| free | 30 |
| basic | 60 |
| pro | 120 |
| enterprise | 300 |

---

## Part 06 · Queues & Jobs (10 marks)

### 6.1 — Import Job with Progress Tracking

**File:** `app/Jobs/ProcessDeliveryImport.php`

**Row-level isolation:** Each row is wrapped in its own try/catch. A bad row increments `failed_rows` and records the error, then processing continues. The entire import is never aborted by one bad row.

**Progress tracking:** `ImportJob.processed_rows` is updated every 100 rows (not every row — that would flood the DB). Clients poll `GET /api/v1/imports/{id}` which returns `progress_percentage`.

**Real-time progress (Part 7.2 intersection):** `DeliveryImportStarted` fires immediately after dispatch and broadcasts on the private `user.{id}` channel, so the frontend gets an instant notification without polling.

### 6.2 — Retry & Failure Handling

**File:** `app/Jobs/SendDeliveryNotification.php`

```php
public int $tries = 5;

public function backoff(): array
{
    // Delays: 1min, 2min, 4min, 8min, 16min
    return [60, 120, 240, 480, 960];
}
```

**Why exponential backoff?** A third-party API that is temporarily unavailable needs time to recover. Hammering it every few seconds could extend its outage. Exponential backoff gives the upstream service progressively more recovery time between attempts.

**Exhausted retries:** `failed()` runs only after all 5 attempts fail. It logs a `critical` level entry and sends an alert to a webhook (Slack/PagerDuty), ensuring no silent failures.

---

## Part 07 · Events & Broadcasting (10 marks)

### 7.1 — Model Observer

**File:** `app/Observers/DeliveryObserver.php`

```php
public function updating(Delivery $delivery): void
{
    if (! $delivery->isDirty('status')) {
        return; // Exit immediately for non-status updates
    }
    // ...
}
```

**Key design:** Uses `updating` (before save) rather than `updated` (after save) so `getOriginal('status')` is still available. `isDirty('status')` short-circuits for unrelated field updates — updating a delivery's `notes` field creates no log entry.

### 7.2 — Real-time Status Updates

**File:** `app/Events/DeliveryStatusChanged.php`

Channel: `private driver.{driver_id}` — only the assigned driver can subscribe.

```php
public function broadcastOn(): array
{
    return [new PrivateChannel("driver.{$this->delivery->driver_id}")];
}
```

**Channel authorization** (`routes/channels.php`):
```php
Broadcast::channel('driver.{driverId}', function (User $user, int $driverId) {
    return (int) $user->id === $driverId && $user->role === 'driver';
});
```

**Frontend subscription** (`resources/js/delivery-realtime.js`):
```js
echo.private(`driver.${driverId}`)
    .listen('.delivery.status.changed', (payload) => {
        updateDeliveryUI(payload.tracking_number, payload.new_status);
    });
```

The `.` prefix on the event name tells Echo to listen for the exact `broadcastAs()` name without namespace prefix.

---

## Part 08 · Caching & Performance (8 marks)

### 8.1 — Cache Strategy for Tenant Route Data

**File:** `app/Services/TenantRouteCache.php`

```php
private function cacheKey(int $tenantId): string
{
    return "tenant:{$tenantId}:routes";
}
```

**Tenant isolation:** Each tenant has its own cache key. Updating Tenant A's routes calls `invalidate($tenantA->id)` which only deletes `tenant:1:routes`. Tenant B's `tenant:2:routes` key is completely unaffected.

**Pattern:** Cache-aside (Lazy Loading). On read: check cache → miss → fetch DB → store in cache → return. On write: write DB → delete cache key. Never update the cache directly on write — it avoids race conditions.

**TTL = 5 minutes:** Route data is stable but not static. A 5-minute TTL limits stale reads to at most 5 minutes after a route update, while dramatically reducing DB load on read-heavy endpoints.

### 8.2 — Fixing a Memory-leaking Command

**File:** `app/Console/Commands/FixMemoryLeakingCommand.php`

Three root causes of memory growth in long-running Artisan commands:

| Cause | Symptom | Fix |
|-------|---------|-----|
| Query log enabled | Every SQL string stored in memory forever | `DB::disableQueryLog()` at command start |
| Chunk collection not released | PHP can't GC models still referenced by the `$deliveries` variable | `unset($deliveries)` at end of each chunk closure |
| Event listeners holding model references | Queued listeners keep a reference to the model alive | Register listeners with `->onQueue('low')` so they execute outside the command process |

---

## Part 09 · Testing (8 marks)

### 9.1 — Feature Test

**File:** `tests/Feature/Feature/DeliveryImportTest.php`

Three test cases:

| Test | What it verifies |
|------|-----------------|
| `it dispatches...` | `Queue::assertPushed(ProcessDeliveryImport)` + `Event::assertDispatched(DeliveryImportStarted)` + response shape (202 + `import_job_id`) |
| `it rejects non-CSV` | Validation rejects `.exe`; returns 422 with `file` error key |
| `it requires authentication` | Unauthenticated request returns 401 |

**Key technique:** `Queue::fake()` and `Event::fake()` replace the real drivers with test doubles. Jobs and events are recorded but never executed — tests stay fast and deterministic.

### 9.2 — Model Factory

**File:** `database/factories/DeliveryFactory.php`

Bangladesh-specific data:

```php
// Dhaka division coordinates (bounding box)
'pickup_lat' => $this->faker->randomFloat(7, 23.65, 23.90),
'pickup_lng' => $this->faker->randomFloat(7, 90.33, 90.50),

// Bangladesh mobile number: 01[3-9] + 8 digits = 11 digits total
'recipient_phone' => '01' . fake()->numberBetween(3, 9) . fake()->numerify('########'),

// Tracking: "BD" prefix + 6 alphanumeric + "??" letters = realistic local format
'tracking_number' => strtoupper($this->faker->bothify('BD######??')),
```

Timestamps are logically ordered: `created_at < picked_up_at < delivered_at`, and `delivered_at` is only set when `status === 'delivered'`.

---

## Part 10 · Code Review & Debugging (12 marks)

### 10.1 — Bug Identification

**File:** `app/Http/Controllers/Api/V1/CodeReviewExamples.php`

#### Snippet A — N+1 inside a transaction-less foreach

**Bug:** Creates one DB write per waypoint in a loop. No transaction means partial data if the process crashes mid-loop.

```php
// WRONG
foreach ($request->waypoints as $waypoint) {
    Waypoint::create([...]); // N separate INSERTs
}

// CORRECT
DB::transaction(function () use ($request) {
    $delivery = Delivery::create([...]);
    $delivery->waypoints()->createMany($request->waypoints); // 1 INSERT ... VALUES (...)
});
```

**Production risk:** 500-row waypoint batch = 500 round trips = ~5 seconds. Any crash after item 50 leaves 50 orphaned waypoints with no delivery parent.

#### Snippet B — DB query on every request

**Bug:** `Tenant::where('api_key', ...)->firstOrFail()` runs on every single HTTP request.

```php
// WRONG — hits DB on every request
$tenant = Tenant::where('api_key', $request->header('X-Tenant-Key'))->firstOrFail();

// CORRECT — cached; only hits DB on first request per key per 5 minutes
$tenant = Cache::remember("tenant:key:{$apiKey}", 300, fn () =>
    Tenant::where('api_key', $apiKey)->where('is_active', true)->first()
);
```

**Production risk:** 1,000 req/s = 1,000 DB queries/s just for tenant resolution. At 10ms per query, the DB spends 10 full seconds per second on auth alone — will saturate the connection pool.

#### Snippet C — Loading 100k rows into PHP memory

**Bug:** `Delivery::all()` loads every row as an Eloquent model. At ~2KB per model, 100k rows = 200MB — hits PHP memory limit.

```php
// WRONG
return response()->json(Delivery::all());

// CORRECT — paginate for live queries
return DeliveryResource::collection(Delivery::paginate(100));

// OR — background export for full dataset (see ReportController)
ExportDeliveriesToCsv::dispatch(auth()->id(), $exportKey);
return response()->json(['export_key' => $exportKey], 202);
```

### 10.2 — Refactoring a Fat Controller

**Before:** 80-line controller action mixing: validation, business logic, DB writes, cache invalidation, notification dispatch.

**After (files):**

| Class | Responsibility | File |
|-------|---------------|------|
| `StoreDeliveryRequest` | Input validation only | `app/Http/Requests/` |
| `CreateDeliveryAction` | Business logic + DB write + cache invalidation | `app/Actions/` |
| `DeliveryObserver` | Side-effects (logging, event dispatch) | `app/Observers/` |
| `SendDeliveryNotification` | Notification dispatch (queued) | `app/Jobs/` |
| `DeliveryController::store()` | HTTP adapter: receive → delegate → respond | `app/Http/Controllers/` |

The resulting controller method:
```php
public function store(StoreDeliveryRequest $request, CreateDeliveryAction $action): JsonResponse
{
    $delivery = $action->execute([
        ...$request->validated(),
        'user_id' => auth()->id(),
        'status'  => 'pending',
    ]);

    return (new DeliveryResource($delivery))->response()->setStatusCode(201);
}
```

### 10.3 — Intermittent 500 Errors Under Load

**Investigation playbook:**

1. **Correlate errors with a shared resource:** Parse logs for patterns — do errors cluster by time of day? By endpoint? By user?

2. **Check DB connection exhaustion:**
   ```sql
   SELECT count(*), state FROM pg_stat_activity GROUP BY state;
   -- If 'idle in transaction' is high → long-running transactions not committed
   ```

3. **Check Redis connection limits:**
   ```
   redis-cli INFO clients
   # connected_clients: 500 → may be exhausting the pool
   ```

4. **Reproduce with load testing:**
   ```bash
   k6 run --vus 100 --duration 30s k6-script.js
   ```

5. **Common root causes in Laravel:**
   - `SESSION_DRIVER=database` + high traffic → `sessions` table row-level locking
     → **Fix:** `SESSION_DRIVER=redis`
   - Cache stampede: many requests miss cache simultaneously → DB flood
     → **Fix:** `Cache::lock('key', 10)->block(5, fn() => Cache::remember(...))`
   - Queue worker memory growth → worker restarts mid-job → 500 on the HTTP side waiting for a result
     → **Fix:** `--max-jobs=500` on Horizon workers
   - Missing DB index causing table locks under concurrent writes
     → **Fix:** Add concurrent index: `CREATE INDEX CONCURRENTLY ...`
   - N+1 query under load exhausting connections
     → **Fix:** Add `with()` eager loading; confirmed via Telescope query panel

---

## Environment Variables Reference

```env
# Application
APP_NAME="Delivery Management"
APP_ENV=local|staging|production
APP_DEBUG=false         # Always false in production
APP_URL=https://api.yourdomain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=delivery_management
DB_USERNAME=your_user
DB_PASSWORD=your_password

# Cache & Queue
CACHE_STORE=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Broadcasting
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=6001
PUSHER_SCHEME=https

# Session
SESSION_DRIVER=redis   # Not 'database' in production
```

---

## Database Schema Diagram

```
tenants
  id, name, slug, api_key, plan, rate_limit_per_minute, is_active, settings
       │
       │ tenant_id (nullable FK)
       ▼
users
  id, tenant_id, name, email, phone, role[admin|driver|customer], password
       │                │
       │ user_id        │ driver_id (nullable)
       ▼                ▼
deliveries
  id, user_id, driver_id, tenant_id, tracking_number, status[ENUM],
  recipient_name, recipient_phone,
  pickup_address, pickup_lat, pickup_lng,
  delivery_address, delivery_lat, delivery_lng,
  weight_kg, notes, scheduled_at, picked_up_at, delivered_at
  [Indexes: (user_id,status,created_at), (driver_id,status), (tenant_id,created_at)]
       │
       │ delivery_id
       ▼
delivery_logs
  id, delivery_id, user_id, from_status, to_status, event,
  notes, metadata[JSON], lat, lng
  [Indexes: (delivery_id, created_at), delivery_id]

delivery_routes
  id, tenant_id, name, description, waypoints[JSON], is_active

import_jobs
  id, user_id, filename, disk, path, status, total_rows,
  processed_rows, failed_rows, errors[JSON], started_at, completed_at
```

