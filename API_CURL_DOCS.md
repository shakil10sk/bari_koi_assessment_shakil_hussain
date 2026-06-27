# API Curl Reference

## Getting a Sanctum Token (one-time setup)

There is no public login endpoint — generate a token via Tinker:

```bash
php artisan tinker --execute="echo \App\Models\User::where('email','admin@example.com')->first()->createToken('test')->plainTextToken;"
```

Copy the token and set these variables before running any request below:

```bash
BASE=http://localhost:8000/api
TOKEN=your_token_here
TENANT_KEY=XwQdYMlllnvpfTyuvX0GOYiJAEzGdAgSCGtvz1f0o58ozGBz0AIetHa0zlUMW45e
```

All requests require:
```
-H "Authorization: Bearer $TOKEN"
-H "Accept: application/json"
-H "X-Tenant-Key: $TENANT_KEY"
```

---

## V1 Deliveries (deprecated — returns `Deprecation` + `Sunset` headers)

```bash
# List (cursor-based pagination)
curl "$BASE/v1/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# List with pagination params
curl "$BASE/v1/deliveries?limit=10&cursor=CURSOR_FROM_PREVIOUS_RESPONSE" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# Create
curl -X POST "$BASE/v1/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -d '{
    "recipient_name": "John Doe",
    "recipient_phone": "01711000000",
    "pickup_address": "Gulshan 2, Dhaka",
    "delivery_address": "Dhanmondi 27, Dhaka",
    "pickup_lat": 23.7925,
    "pickup_lng": 90.4078,
    "delivery_lat": 23.7461,
    "delivery_lng": 90.3742,
    "scheduled_at": "2026-06-28T10:00:00Z",
    "notes": "Handle with care"
  }'

# Show
curl "$BASE/v1/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# Update status
curl -X PUT "$BASE/v1/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -d '{"status": "in_transit", "notes": "On the way"}'

# Delete
curl -X DELETE "$BASE/v1/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"
```

Valid statuses: `pending`, `assigned`, `picked_up`, `in_transit`, `delivered`, `failed`, `cancelled`

---

## V2 Deliveries (current)

```bash
# List (Laravel cursor pagination, includes driver/user)
curl "$BASE/v2/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# Create (includes driver_id)
curl -X POST "$BASE/v2/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -d '{
    "recipient_name": "Jane Smith",
    "recipient_phone": "01811000000",
    "pickup_address": "Banani 11, Dhaka",
    "delivery_address": "Mirpur 10, Dhaka",
    "driver_id": 2,
    "scheduled_at": "2026-06-29T09:00:00Z"
  }'

# Show
curl "$BASE/v2/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# Update (can also reassign driver)
curl -X PUT "$BASE/v2/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -d '{"status": "assigned", "driver_id": 3}'

# Delete
curl -X DELETE "$BASE/v2/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"
```

---

## V1 Imports (CSV upload → async job)

```bash
# Upload a CSV file (returns 202 + job ID immediately)
curl -X POST "$BASE/v1/imports" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -F "file=@/path/to/deliveries.csv"

# Poll job status (use import_job_id from response above)
curl "$BASE/v1/imports/JOB_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"
```

---

## V1 Reports (async weekly report)

```bash
# Trigger weekly report generation (returns 202 + report_key)
curl -X POST "$BASE/v1/reports/weekly" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -d '{}'

# Poll report status (use report_key from above)
curl "$BASE/v1/reports/REPORT_KEY/status" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"
```

---

## V1 Exports (CSV export → async)

```bash
# Trigger CSV export (returns 202 + export_key)
curl -X POST "$BASE/v1/exports/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# Poll export status / get download URL (use export_key from above)
curl "$BASE/v1/exports/EXPORT_KEY/status" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# Download the CSV (use the signed `url` from the status response — expires in 1 hour)
curl -L "SIGNED_URL_FROM_STATUS_RESPONSE" \
  -H "Authorization: Bearer $TOKEN" \
  --output deliveries-export.csv
```

---

## V1 Routes (tenant-scoped, cache-aside)

```bash
# List routes for tenant
curl "$BASE/v1/routes" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# Create a route
curl -X POST "$BASE/v1/routes" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -d '{
    "name": "Uttara → Motijheel Express",
    "description": "North-south commuter route",
    "waypoints": [
      {"lat": 23.8759, "lng": 90.3795, "address": "Uttara Sector 7, Dhaka"},
      {"lat": 23.7461, "lng": 90.3742, "address": "Dhanmondi 27, Dhaka"},
      {"lat": 23.7224, "lng": 90.4088, "address": "Motijheel, Dhaka"}
    ]
  }'

# Show a route
curl "$BASE/v1/routes/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"

# Update a route
curl -X PUT "$BASE/v1/routes/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" \
  -d '{"name": "Updated Route Name", "is_active": false}'

# Delete a route
curl -X DELETE "$BASE/v1/routes/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY"
```

---

## Tips

Pretty-print any response with `jq`:
```bash
curl "$BASE/v2/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "X-Tenant-Key: $TENANT_KEY" | jq
```

Make sure these are running before testing:
```bash
php artisan serve          # web server
php artisan queue:work     # required for imports / reports / exports
```
