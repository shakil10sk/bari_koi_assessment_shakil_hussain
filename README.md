# Delivery Management System — Laravel Developer Assessment

A production-ready backend API for a Bangladesh-based delivery management service, built as a comprehensive skills assessment covering all layers of a Laravel application.

---

## Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 11+ (PHP 8.2+) |
| Database | PostgreSQL 16 |
| Cache / Queue | Redis (via Predis) |
| Queue Monitor | Laravel Horizon |
| Real-time | Pusher / Laravel Reverb (Broadcasting) |
| Auth | Laravel Sanctum (token-based) |
| CSV Export | League/CSV |
| Testing | Pest 4 |

---

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- PostgreSQL 14+
- Redis 7+

### Installation

```bash
git clone <repo-url> delivery-management
cd delivery-management
composer install
cp .env.example .env
php artisan key:generate
```

### Configure `.env`

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=delivery_management
DB_USERNAME=your_pg_user
DB_PASSWORD=your_pg_password

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

QUEUE_CONNECTION=redis
CACHE_STORE=redis
BROADCAST_CONNECTION=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-app-key
PUSHER_APP_SECRET=your-app-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
```

### Database Setup

```bash
psql -U your_pg_user -c "CREATE DATABASE delivery_management;"
php artisan migrate
php artisan db:seed   # 50 deliveries, 2 tenants, drivers, customers
```

### Running the Application

```bash
php artisan serve           # API server
php artisan queue:work redis # Queue worker (required for jobs)
php artisan horizon          # Production queue dashboard → http://localhost:8000/horizon
```

---

## Testing

```bash
psql -U your_pg_user -c "CREATE DATABASE delivery_management_test;"
php artisan test
php artisan test --filter=DeliveryImport
php artisan test --filter=DeliveryObserver
php artisan test --filter=CursorPagination
```

---

## API Reference

All endpoints require `Authorization: Bearer {token}` from Sanctum.

### Authentication

```bash
# Login — returns a Sanctum token
POST /api/login
Content-Type: application/json
{"email": "admin@example.com", "password": "password"}

# Logout — revokes the current token
POST /api/logout
Authorization: Bearer {token}
```

> **Dev shortcut:** generate a token directly via Tinker if you don't want to call the login endpoint:
> ```bash
> php artisan tinker --execute="echo \App\Models\User::where('email','admin@example.com')->first()->createToken('test')->plainTextToken;"
> ```

### Required Headers (all v1 / v2 routes)

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer {token}` |
| `Accept` | `application/json` |
| `X-Tenant-Key` | `{tenant_api_key}` |

### V1 Endpoints (Deprecated — include `Deprecation` / `Sunset` headers)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/deliveries` | Cursor-paginated deliveries |
| POST | `/api/v1/deliveries` | Create delivery |
| GET | `/api/v1/deliveries/{id}` | Get delivery |
| PUT | `/api/v1/deliveries/{id}` | Update delivery status / fields |
| DELETE | `/api/v1/deliveries/{id}` | Soft-delete delivery |
| POST | `/api/v1/imports` | Upload CSV for background import |
| GET | `/api/v1/imports/{id}` | Import job progress |
| POST | `/api/v1/reports/weekly` | Queue weekly report generation |
| GET | `/api/v1/reports/{key}/status` | Poll report status |
| POST | `/api/v1/exports/deliveries` | Queue CSV export |
| GET | `/api/v1/exports/{key}/status` | Poll export status + signed download URL |
| GET | `/api/v1/exports/{key}/download` | Download the exported CSV (signed URL) |
| GET | `/api/v1/routes` | List tenant delivery routes (cached) |
| POST | `/api/v1/routes` | Create a route |
| GET | `/api/v1/routes/{id}` | Get a route |
| PUT | `/api/v1/routes/{id}` | Update a route |
| DELETE | `/api/v1/routes/{id}` | Delete a route |

### V2 Endpoints (Current)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2/deliveries` | Cursor-paginated (nested `assigned_agent`) |
| POST | `/api/v2/deliveries` | Create delivery (supports `driver_id`) |
| GET | `/api/v2/deliveries/{id}` | Full nested structure |
| PUT | `/api/v2/deliveries/{id}` | Update delivery / reassign driver |
| DELETE | `/api/v2/deliveries/{id}` | Soft-delete delivery |

> See `API_CURL_DOCS.md` for ready-to-run `curl` examples for every endpoint above.

### Cursor Pagination Response

```json
{
  "data": [...],
  "meta": {
    "next_cursor": "dXNlcjox.a3f8d2c1b9e4",
    "has_more": true,
    "limit": 20
  }
}
```

Cursors are HMAC-signed (SHA-256) — tampering returns `422 Unprocessable Entity`.

---

## Assessment — Problems & Solutions

### Part 01 · Database & Eloquent Basics (10 marks)

**What was required:**
Design a production-ready schema for `users`, `deliveries`, and `delivery_logs` with migrations, then write Eloquent relationships and a single efficient query that returns deliveries with the latest log and a log count.

**How I solved it:**

*Schema design (`database/migrations/`):*

- `deliveries` — links `user_id` and `driver_id` to `users`, `tenant_id` to `tenants`. Uses an `enum` for the seven lifecycle statuses (`pending → assigned → picked_up → in_transit → delivered / failed / cancelled`). Stores both pickup and delivery coordinates as `decimal(10,7)`. Applies `softDeletes()` so deleted records are recoverable. Includes composite indexes on `(driver_id, status)` and `(tenant_id, status, created_at)` inside the create migration.
- `delivery_logs` — immutable audit trail with `from_status`, `to_status`, and `event` columns. Stores optional GPS coordinates at the moment of the event. Indexed on `(delivery_id, created_at)` so the latest-log lookup is a single index scan.
- `users` — extended with `role` (customer / driver / admin) and `tenant_id` foreign key.

*Eloquent relationships (`app/Models/Delivery.php`):*

```php
public function user(): BelongsTo { return $this->belongsTo(User::class); }
public function driver(): BelongsTo { return $this->belongsTo(User::class, 'driver_id'); }
public function logs(): HasMany { return $this->hasMany(DeliveryLog::class); }
public function latestLog(): HasOne { return $this->hasOne(DeliveryLog::class)->latestOfMany(); }
```

*Efficient single query:*

```php
Delivery::query()
    ->where('user_id', $userId)
    ->withCount('logs')
    ->with(['latestLog'])
    ->latest()
    ->get();
```

`withCount` adds a single `COUNT` subquery. `with(['latestLog'])` uses `latestOfMany()` which emits a single `LIMIT 1` subquery per batch — no N+1, no application-side sorting.

---

### Part 02 · Query Optimization (10 marks)

**What was required:**
Design an indexing strategy for 100,000+ delivery rows filtered by `user_id`, `status`, and a `created_at` date range. Then describe a process for diagnosing a 10-second join query.

**How I solved it:**

*Index strategy (`database/migrations/2026_06_27_050543_add_indexes_to_deliveries_table.php`):*

```php
$table->index(['user_id', 'status', 'created_at'], 'idx_deliveries_user_status_date');
$table->index(['tenant_id', 'created_at'],          'idx_deliveries_tenant_date');
$table->index(['status', 'created_at'],              'idx_deliveries_status_date');
```

The composite `(user_id, status, created_at)` index satisfies the three-column filter in one index scan with no table heap access for covered columns. Column order follows selectivity — `user_id` is most selective, `status` narrows further, `created_at` supports range predicates on the narrow result set.

*Diagnosing a slow join (`app/Services/QueryDiagnosticService.php`):*

The `QueryDiagnosticService` captures the full diagnostic process:

1. Run `EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)` on the exact slow query to see which nodes are sequential scans vs. index scans and where the time is spent.
2. Query `pg_stat_activity` to find blocking or long-running transactions.
3. Check `pg_stats` for missing indexes and high `n_distinct` columns that would benefit from indexing.
4. Replace correlated subqueries (`SELECT … LIMIT 1` per row) with `DISTINCT ON` or a lateral join — the `optimisedQuery()` method shows the rewrite.

The service ships as a developer tool rather than a live endpoint so diagnostics can be run safely from Tinker or a one-off Artisan command.

---

### Part 03 · Handling Large Datasets (12 marks)

**What was required:**
- 3.1 Rewrite a command that crashes with OOM when calling `->get()` on 100,000 rows.
- 3.2 Replace offset pagination (slow beyond page 200) with cursor-based pagination.
- 3.3 Return quickly from a slow report endpoint instead of blocking until it finishes.

**How I solved it:**

*3.1 — Memory-safe notifications (`app/Console/Commands/ProcessDeliveryNotifications.php`):*

Replaced `Delivery::where(...)->get()` with `->cursor()`. The cursor uses a PHP generator backed by a database server-side cursor — only one row is held in memory at a time regardless of table size.

```php
Delivery::where('status', 'pending')
    ->cursor()
    ->each(fn ($delivery) => SendDeliveryNotification::dispatch($delivery));
```

*3.2 — Cursor pagination (`app/Http/Controllers/Api/V1/DeliveryController.php`):*

Replaced `OFFSET n` with an ID-keyed seek: `WHERE id > :last_seen_id LIMIT n+1`. The extra row tells us whether a next page exists. The cursor value is `base64(id)` + an HMAC-SHA256 signature truncated to 16 characters — tampering is detected with `hash_equals` and returns `422`.

```json
{ "meta": { "next_cursor": "dXNlcjox.a3f8d2c1b9e4", "has_more": true, "limit": 20 } }
```

*3.3 — Deferred report (`app/Http/Controllers/Api/V1/ReportController.php` + `app/Jobs/GenerateDeliveryReport.php`):*

The endpoint immediately returns `202 Accepted` with a `report_key` and a `status_url`. The actual work runs in `GenerateDeliveryReport` on the queue. The client polls `/api/v1/reports/{key}/status`; when the key's cache entry flips to `ready`, the response includes the data. Gateway never times out; queue workers scale independently.

---

### Part 04 · Export Pipelines (10 marks)

**What was required:**
- 4.1 A background job that exports 50,000 rows to CSV without OOM, with a one-hour download link.
- 4.2 A dashboard aggregation query grouped by week for the last 3 months.

**How I solved it:**

*4.1 — CSV export job (`app/Jobs/ExportDeliveriesToCsv.php`):*

Uses League/CSV `Writer` writing to a `tempnam` file. Records are streamed in chunks of 500 via `->chunk(500, …)` so PHP never holds the full dataset. After writing, the file is moved to `Storage::disk('local')` and a **signed route** (`URL::temporarySignedRoute`) is generated that expires in one hour. The download URL and status are stored in Redis. The controller endpoint that serves the download verifies the signature before streaming the file.

*4.2 — Weekly aggregation query (`app/Jobs/GenerateDeliveryReport.php`):*

```sql
SELECT
    date_trunc('week', created_at) AS week,
    COUNT(*) AS total_deliveries,
    ROUND(100.0 * SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END)
          / NULLIF(COUNT(*), 0), 2) AS success_rate,
    ROUND(AVG(EXTRACT(EPOCH FROM (delivered_at - created_at)) / 3600)::numeric, 2)
          AS avg_delivery_hours
FROM deliveries
WHERE created_at >= NOW() - INTERVAL '3 months'
  AND deleted_at IS NULL
GROUP BY date_trunc('week', created_at)
ORDER BY week
```

This is **cached for one hour** because the data changes continuously and recalculating it on every dashboard load is expensive. The `GenerateDeliveryReport` job runs the query in the background and stores the result in Redis; the API returns the cached value instantly.

---

### Part 05 · API Design (10 marks)

**What was required:**
- 5.1 Build v1 and v2 versioned API resources where v2 restructures the driver as a nested `assigned_agent` object. v1 must signal deprecation to clients.
- 5.2 A `TenantMiddleware` that identifies and validates the tenant efficiently under load, plus tenant-plan-aware rate limiting.

**How I solved it:**

*5.1 — Versioned resources:*

`app/Http/Resources/V1/DeliveryResource.php` returns a flat structure with `user_id` and `driver_id` as scalar IDs.

`app/Http/Resources/V2/DeliveryResource.php` restructures the response:

```json
{
  "assigned_agent": { "id": 5, "name": "Rahim", "email": "...", "phone": "...", "role": "driver" },
  "customer":       { "id": 1, "name": "Karim", "email": "..." },
  "pickup":         { "address": "Gulshan", "lat": 23.78, "lng": 90.41 },
  "destination":    { "address": "Banani", "lat": 23.79, "lng": 90.40, "recipient": "...", "phone": "..." }
}
```

`app/Http/Middleware/DeprecatedApiMiddleware.php` automatically appends `Deprecation` and `Sunset` response headers to every v1 response so API clients and monitoring tools can detect the deprecation without reading docs.

*5.2 — Tenant middleware with caching (`app/Http/Middleware/TenantMiddleware.php`):*

The middleware reads `X-Tenant-Key` from the request header, then uses `Cache::remember` (5-minute TTL) to avoid a database hit on every request. The tenant's plain attribute array is cached — not the Eloquent model — to avoid serialization issues with Redis. The resolved tenant is bound into the IoC container (`app()->instance('tenant', $tenant)`) so any downstream code can resolve it cleanly.

Rate limiting is configured in `app/Providers/AppServiceProvider.php` using `RateLimiter::for('api', …)` with a per-tenant limit that reads the tenant's `plan` field and applies different caps (e.g. 60 rpm for free, 600 rpm for pro).

---

### Part 06 · Queues & Jobs (10 marks)

**What was required:**
- 6.1 An import job for up to 5,000 CSV rows with per-row error isolation and live progress tracking.
- 6.2 Exponential back-off retries for `SendDeliveryNotification` with failure logging and alerting when all retries are exhausted.

**How I solved it:**

*6.1 — Import with progress (`app/Jobs/ProcessDeliveryImport.php`):*

The `ImportJob` model row is updated every 100 processed records. Row-level errors are caught individually (`try/catch` inside the `foreach`), recorded in the `errors` JSON column, and the `failed_rows` counter is incremented — a bad row never aborts the entire import. The client polls `GET /api/v1/imports/{id}` which reads the live `ImportJob` record and returns `processed_rows`, `failed_rows`, `status`, and the `errors` array.

*6.2 — Retry strategy (`app/Jobs/SendDeliveryNotification.php`):*

```php
public int $tries = 5;
public int $maxExceptions = 3;

public function backoff(): array
{
    return [60, 120, 240, 480, 960]; // seconds: 1m → 2m → 4m → 8m → 16m
}
```

After the fifth attempt the `failed()` hook fires: it writes a `critical` log entry and posts a webhook alert to a Slack / PagerDuty endpoint configured in `services.alert.webhook`.

---

### Part 07 · Events & Broadcasting (10 marks)

**What was required:**
- 7.1 A `DeliveryObserver` that logs status changes only, capturing both old and new status.
- 7.2 Real-time frontend updates to the assigned driver via broadcasting, with secure channel authorization.

**How I solved it:**

*7.1 — Observer (`app/Observers/DeliveryObserver.php`):*

Hooks into the `updating` lifecycle event (before the write commits). Uses `$delivery->isDirty('status')` to exit immediately if the status column has not changed — no log spam for unrelated field updates.

```php
public function updating(Delivery $delivery): void
{
    if (! $delivery->isDirty('status')) return;

    DeliveryLog::create([
        'from_status' => $delivery->getOriginal('status'),
        'to_status'   => $delivery->status,
        'event'       => 'status_changed',
        ...
    ]);

    DeliveryStatusChanged::dispatch($delivery, $delivery->getOriginal('status'));
}
```

*7.2 — Broadcasting (`app/Events/DeliveryStatusChanged.php`, `routes/channels.php`):*

`DeliveryStatusChanged` implements `ShouldBroadcast` and broadcasts on a private channel `driver.{driver_id}`. The payload includes `delivery_id`, `tracking_number`, `previous_status`, `new_status`, and `updated_at`.

Channel authorization in `routes/channels.php`:

```php
Broadcast::channel('driver.{driverId}', function (User $user, int $driverId) {
    return (int) $user->id === $driverId && $user->role === 'driver';
});
```

Only the driver whose ID matches the channel can subscribe. The frontend subscribes via:

```js
Echo.private(`driver.${driverId}`)
    .listen('.delivery.status.changed', (e) => {
        console.log(e.new_status); // update UI
    });
```

---

### Part 08 · Caching & Performance (8 marks)

**What was required:**
- 8.1 Per-tenant route caching that is invalidated immediately on write without leaking between tenants.
- 8.2 Identify and fix the causes of memory growth in a command processing 100,000 records.

**How I solved it:**

*8.1 — Tenant route cache (`app/Services/TenantRouteCache.php`):*

Each tenant gets its own Redis key: `tenant:{tenantId}:routes`. Calling `invalidate(tenantId)` runs a single `Cache::forget` on that key only — other tenants are completely unaffected. TTL is 300 seconds. Cache is warmed on read (`Cache::remember`) and invalidated in `CreateDeliveryAction` immediately after any write that affects routes.

```php
private function cacheKey(int $tenantId): string
{
    return "tenant:{$tenantId}:routes";
}
```

*8.2 — Memory leak fix (`app/Console/Commands/FixMemoryLeakingCommand.php`):*

Common causes in long-running Artisan commands:

1. **Query log accumulation** — `DB::enableQueryLog()` (the default) keeps every query string in memory. Fixed with `DB::disableQueryLog()` at the top of `handle()`.
2. **Loading unnecessary columns** — `SELECT *` loads BLOBs and large text fields. Fixed with `->select(['id', 'status', 'tracking_number', 'user_id'])`.
3. **Collection reference not released** — Laravel's `chunk()` re-uses variables. Fixed by calling `unset($deliveries)` at the end of each chunk callback.
4. **Using `cursor()` without explicit ordering** — on large tables, using `->orderBy('id')` ensures the database uses the primary key index rather than a full sort.

```php
DB::disableQueryLog();

Delivery::query()
    ->select(['id', 'status', 'tracking_number', 'user_id'])
    ->orderBy('id')
    ->chunk(500, function ($deliveries) {
        foreach ($deliveries as $delivery) { /* process */ }
        unset($deliveries);
    });
```

---

### Part 09 · Testing (8 marks)

**What was required:**
- 9.1 A Pest feature test for `POST /api/v1/imports` that verifies job dispatched, event fired, and response shape — without running the job or sending the event.
- 9.2 A `DeliveryFactory` with realistic Bangladesh-specific data.

**How I solved it:**

*9.1 — Feature test (`tests/Feature/Feature/DeliveryImportTest.php`):*

Uses `Queue::fake()`, `Event::fake()`, and `Storage::fake('local')` so nothing actually executes. Three test cases:

```php
it('dispatches ProcessDeliveryImport job and DeliveryImportStarted event on valid CSV upload', function () {
    // ...
    $response->assertStatus(202)
             ->assertJsonStructure(['message', 'import_job_id', 'status_url']);

    Queue::assertPushed(ProcessDeliveryImport::class, fn ($job) => $job->importJob->user_id === $user->id);
    Event::assertDispatched(DeliveryImportStarted::class);
});

it('rejects non-CSV files', ...);    // 422 with validation error on 'file'
it('requires authentication', ...);  // 401
```

*9.2 — Factory (`database/factories/DeliveryFactory.php`):*

- **Dhaka coordinates** — latitude `23.65–23.90°N`, longitude `90.33–90.50°E` (actual bounding box for greater Dhaka).
- **BD phone numbers** — `01[3-9]XXXXXXXX` matching the Bangladeshi mobile number format.
- **Status-consistent timestamps** — `picked_up_at` is only set when status is `picked_up`, `in_transit`, or `delivered`; `delivered_at` is only set on `delivered`, and is always after `picked_up_at`.
- Named states: `DeliveryFactory::new()->delivered()` and `->pending()` for convenient use in tests.

---

### Part 10 · Code Review & Debugging (12 marks)

**What was required:**
- 10.1 Identify and fix three code snippets with production bugs.
- 10.2 Refactor an 80-line fat controller into properly separated concerns.
- 10.3 Describe the process for investigating intermittent 500 errors under load.

**How I solved it:**

*10.1 — Bug review (`app/Http/Controllers/Api/V1/CodeReviewExamples.php`):*

**Snippet A — N+1 writes inside a `foreach` loop:**

| | Before | After |
|---|---|---|
| Problem | One `INSERT` per waypoint; no transaction — partial writes on crash | `DB::transaction()` wraps everything; `createMany()` batches the inserts |
| Fix | `CreateDeliveryAction` wraps all writes in a single transaction |

**Snippet B — DB lookup on every request in middleware:**

| | Before | After |
|---|---|---|
| Problem | `Tenant::where('api_key', …)->firstOrFail()` on every request saturates the connection pool under load | `Cache::remember("tenant:key:{$apiKey}", 300, …)` — one DB hit per key per 5 minutes |
| Fix | `TenantMiddleware` (see Part 5.2) |

**Snippet C — `Delivery::all()` on a 100k-row table:**

| | Before | After |
|---|---|---|
| Problem | PHP OOM at ~10k rows; client timeout before the response arrives | Return `202` immediately; run export in `ExportDeliveriesToCsv` job; client polls for completion |
| Fix | `ReportController` + `GenerateDeliveryReport` / `ExportDeliveriesToCsv` (see Parts 3.3 and 4.1) |

*10.2 — Fat controller refactor:*

The 80-line controller was broken into dedicated classes:

| Concern | Class |
|---------|-------|
| Input validation | `app/Http/Requests/StoreDeliveryRequest.php` |
| Business logic + DB write | `app/Actions/CreateDeliveryAction.php` |
| Cache invalidation | Called inside `CreateDeliveryAction` via `TenantRouteCache::invalidate()` |
| Response shaping | `app/Http/Resources/V1/DeliveryResource.php` |
| Notification dispatch | `SendDeliveryNotification` job dispatched from the Action |

The controller becomes a thin coordinator: `$action->execute($request->validated())` and `return new DeliveryResource($delivery)`.

*10.3 — Intermittent 500s under load:*

1. **Collect symptoms** — check `storage/logs/laravel.log` for patterns (`Too many connections`, `Deadlock found`, `could not obtain lock`).
2. **Inspect the DB** — query `pg_stat_activity` for idle-in-transaction connections holding locks. Check connection pool utilization.
3. **Profile with EXPLAIN ANALYZE** — run the queries that correlate with error spikes under concurrent load.
4. **Load-test with isolation** — reproduce with `k6` while Horizon is running; watch queue backlog and worker memory.
5. **Common root causes and fixes in Laravel:**

| Root cause | Fix |
|---|---|
| N+1 inside a loop creating per-request transactions | Batch operations, wrap in one transaction |
| Cache stampede (many concurrent misses) | Use `Cache::lock()` for atomic computation |
| `SESSION_DRIVER=database` under high traffic | Switch to `SESSION_DRIVER=redis` |
| Horizon workers growing in memory over time | Set `--max-jobs=500` on workers to force periodic restarts |
| DB connection pool exhaustion | Tune `pool_size`, ensure no idle-in-transaction leaks |

---

## Project Structure

```
app/
├── Actions/
│   └── CreateDeliveryAction.php          # Fat-controller refactor — business logic layer
├── Console/Commands/
│   ├── ProcessDeliveryNotifications.php  # Part 3.1 — cursor-based memory-safe dispatch
│   └── FixMemoryLeakingCommand.php       # Part 8.2 — memory leak fixes
├── Events/
│   ├── DeliveryStatusChanged.php         # Part 7.2 — broadcasting event
│   └── DeliveryImportStarted.php         # Part 9.1 — testable event
├── Http/
│   ├── Controllers/Api/
│   │   ├── V1/DeliveryController.php     # Part 3.2 — cursor pagination
│   │   ├── V1/ImportController.php       # Part 6.1 — import + progress
│   │   ├── V1/ReportController.php       # Part 3.3 — async report
│   │   ├── V1/DeliveryRouteController.php# Part 8.1 — tenant route cache
│   │   ├── V1/CodeReviewExamples.php     # Part 10.1 — bug review + fixes
│   │   └── V2/DeliveryController.php     # Part 5.1 — v2 nested resource
│   ├── Middleware/
│   │   ├── TenantMiddleware.php          # Part 5.2 — cached tenant resolution
│   │   └── DeprecatedApiMiddleware.php   # Part 5.1 — Deprecation headers
│   ├── Requests/
│   │   └── StoreDeliveryRequest.php      # Part 10.2 — extracted validation
│   └── Resources/
│       ├── V1/DeliveryResource.php       # Part 5.1 — flat v1 shape
│       ├── V2/DeliveryResource.php       # Part 5.1 — nested v2 shape
│       ├── V1/DeliveryRouteResource.php
│       └── DeliveryLogResource.php
├── Jobs/
│   ├── ProcessDeliveryImport.php         # Part 6.1 — per-row error isolation
│   ├── SendDeliveryNotification.php      # Part 6.2 — exponential back-off + alert
│   ├── ExportDeliveriesToCsv.php         # Part 4.1 — chunked CSV + signed URL
│   └── GenerateDeliveryReport.php        # Parts 3.3, 4.2 — async weekly report
├── Models/
│   ├── Delivery.php                      # Part 1.2 — relationships + forUserWithLogSummary()
│   ├── DeliveryLog.php
│   ├── DeliveryRoute.php
│   ├── ImportJob.php                     # Part 6.1 — progress tracking model
│   ├── Tenant.php
│   └── User.php
├── Observers/
│   └── DeliveryObserver.php              # Part 7.1 — status-change audit log
├── Policies/
│   ├── DeliveryPolicy.php
│   └── ImportJobPolicy.php
├── Providers/
│   └── AppServiceProvider.php            # Part 5.2 — rate limiter registration
└── Services/
    ├── TenantRouteCache.php              # Part 8.1 — per-tenant Redis cache
    └── QueryDiagnosticService.php        # Part 2.2 — EXPLAIN + pg_stat_activity
```

---

## Artisan Commands

```bash
# Part 3.1 — dispatch notifications for all pending deliveries (memory-safe via cursor)
php artisan deliveries:notify-all

# Part 8.2 — process 100k records without memory exhaustion
php artisan deliveries:process-large --chunk=500
```

---

## Horizon

Horizon provides a web UI for queue monitoring:

```bash
php artisan horizon
# Dashboard: http://localhost:8000/horizon
```

Queue configuration is in `config/horizon.php`. Jobs are processed on the `redis` connection with auto-scaling workers.
