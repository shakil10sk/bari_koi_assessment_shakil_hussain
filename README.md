# Delivery Management System — Laravel Developer Assessment

A production-ready backend API for a Bangladesh-based delivery management service, built as a comprehensive skills assessment covering all layers of a Laravel application.

---

## Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 (PHP 8.4) |
| Database | PostgreSQL 16 |
| Cache / Queue | Redis (via Predis) |
| Queue Monitor | Laravel Horizon |
| Real-time | Pusher / Laravel Broadcasting |
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
# Clone and install dependencies
git clone <repo-url> delivery-management
cd delivery-management
composer install

# Environment
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
# Create database
psql -U your_pg_user -c "CREATE DATABASE delivery_management;"

# Run migrations
php artisan migrate

# Seed realistic test data (50 deliveries, 2 tenants, drivers, customers)
php artisan db:seed
```

### Running the Application

```bash
# API server
php artisan serve

# Queue worker (required for jobs)
php artisan queue:work redis

# Horizon (production queue dashboard)
php artisan horizon

# View Horizon dashboard at: http://localhost:8000/horizon
```

---

## Testing

```bash
# Create test database
psql -U your_pg_user -c "CREATE DATABASE delivery_management_test;"

# Run full test suite (21 tests)
php artisan test

# Run a specific suite
php artisan test --filter=DeliveryImport
php artisan test --filter=DeliveryObserver
php artisan test --filter=CursorPagination
```

---

## API Reference

All API endpoints require `Authorization: Bearer {token}` from Sanctum.

### Authentication

```bash
# Register / obtain token (standard Sanctum flow)
POST /api/sanctum/token
```

### V1 Endpoints (Deprecated — include Deprecation/Sunset headers)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/deliveries` | Cursor-paginated deliveries |
| POST | `/api/v1/deliveries` | Create delivery |
| GET | `/api/v1/deliveries/{id}` | Get delivery |
| PATCH | `/api/v1/deliveries/{id}` | Update delivery |
| DELETE | `/api/v1/deliveries/{id}` | Soft-delete delivery |
| POST | `/api/v1/imports` | Upload CSV for background import |
| GET | `/api/v1/imports/{id}` | Import job progress |
| POST | `/api/v1/reports/weekly` | Queue weekly report generation |
| GET | `/api/v1/reports/{key}/status` | Poll report status |
| POST | `/api/v1/exports/deliveries` | Queue CSV export |
| GET | `/api/v1/exports/{key}/status` | Poll export + get download URL |
| GET/POST/PUT/DELETE | `/api/v1/routes` | Tenant delivery routes (cached) |

### V2 Endpoints (Current)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2/deliveries` | Cursor-paginated (nested `assigned_agent`) |
| POST | `/api/v2/deliveries` | Create delivery |
| GET | `/api/v2/deliveries/{id}` | Get delivery (full nested structure) |
| PATCH | `/api/v2/deliveries/{id}` | Update delivery |
| DELETE | `/api/v2/deliveries/{id}` | Soft-delete delivery |

### Tenant Authentication (Middleware)

Include `X-Tenant-Key: {api_key}` header on routes that use `TenantMiddleware`.

### Cursor Pagination

```json
GET /api/v1/deliveries?limit=20&cursor=<cursor_token>

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

## Project Structure

```
app/
├── Actions/
│   └── CreateDeliveryAction.php
├── Console/Commands/
│   ├── ProcessDeliveryNotifications.php
│   └── FixMemoryLeakingCommand.php
├── Events/
│   ├── DeliveryStatusChanged.php
│   └── DeliveryImportStarted.php
├── Http/
│   ├── Controllers/Api/
│   │   ├── V1/DeliveryController.php
│   │   ├── V1/ImportController.php
│   │   ├── V1/ReportController.php
│   │   ├── V1/DeliveryRouteController.php
│   │   ├── V1/CodeReviewExamples.php
│   │   └── V2/DeliveryController.php
│   ├── Middleware/
│   │   ├── TenantMiddleware.php
│   │   └── DeprecatedApiMiddleware.php
│   ├── Requests/
│   │   └── StoreDeliveryRequest.php
│   └── Resources/
│       ├── V1/DeliveryResource.php
│       ├── V2/DeliveryResource.php
│       ├── V1/DeliveryRouteResource.php
│       └── DeliveryLogResource.php
├── Jobs/
│   ├── ProcessDeliveryImport.php
│   ├── SendDeliveryNotification.php
│   ├── ExportDeliveriesToCsv.php
│   └── GenerateDeliveryReport.php
├── Models/
│   ├── User.php
│   ├── Tenant.php
│   ├── Delivery.php
│   ├── DeliveryLog.php
│   ├── DeliveryRoute.php
│   └── ImportJob.php
├── Observers/
│   └── DeliveryObserver.php
├── Policies/
│   ├── DeliveryPolicy.php
│   └── ImportJobPolicy.php
├── Providers/
│   └── AppServiceProvider.php
└── Services/
    ├── TenantRouteCache.php
    └── QueryDiagnosticService.php
```

---

## Artisan Commands

```bash
# Part 3.1 — Dispatch notifications for all pending deliveries (memory-safe)
php artisan deliveries:notify-all

# Part 8.2 — Process 100k records without memory exhaustion
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

