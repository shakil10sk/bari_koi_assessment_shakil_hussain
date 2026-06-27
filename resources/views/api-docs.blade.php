<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Management API Docs</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f1117;
            color: #e2e8f0;
            line-height: 1.6;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: 240px; height: 100vh;
            background: #1a1d27;
            border-right: 1px solid #2d3148;
            padding: 24px 0;
            overflow-y: auto;
            z-index: 100;
        }

        .sidebar-logo {
            padding: 0 20px 24px;
            border-bottom: 1px solid #2d3148;
            margin-bottom: 16px;
        }

        .sidebar-logo h2 {
            font-size: 14px;
            font-weight: 700;
            color: #a78bfa;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-logo p {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }

        .nav-section {
            padding: 8px 20px 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            font-size: 13px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.15s;
            border-left: 2px solid transparent;
        }

        .nav-link:hover { color: #e2e8f0; background: #1e2130; }
        .nav-link.active { color: #a78bfa; border-left-color: #a78bfa; background: #1e2130; }

        .method-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 3px;
            letter-spacing: 0.5px;
        }

        .get  { background: #064e3b; color: #34d399; }
        .post { background: #1e3a5f; color: #60a5fa; }
        .put  { background: #451a03; color: #fb923c; }
        .del  { background: #450a0a; color: #f87171; }

        /* Main content */
        .main {
            margin-left: 240px;
            padding: 40px 48px;
            max-width: 960px;
        }

        .hero {
            background: linear-gradient(135deg, #1e1b4b 0%, #1a1d27 100%);
            border: 1px solid #312e81;
            border-radius: 12px;
            padding: 36px;
            margin-bottom: 40px;
        }

        .hero h1 { font-size: 28px; font-weight: 700; color: #fff; }
        .hero p  { color: #94a3b8; margin-top: 8px; font-size: 15px; }

        .badge-row { display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; }
        .tag {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 500;
        }
        .tag-purple { background: #3730a3; color: #c4b5fd; }
        .tag-green  { background: #064e3b; color: #34d399; }
        .tag-blue   { background: #1e3a5f; color: #60a5fa; }

        /* Setup box */
        .setup-box {
            background: #1a1d27;
            border: 1px solid #2d3148;
            border-radius: 10px;
            padding: 24px;
            margin-bottom: 40px;
        }

        .setup-box h3 { font-size: 14px; color: #a78bfa; font-weight: 600; margin-bottom: 12px; }

        /* Section */
        .section { margin-bottom: 48px; }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #2d3148;
        }

        .section-header h2 { font-size: 18px; font-weight: 600; color: #f1f5f9; }

        .deprecated-badge {
            font-size: 11px;
            padding: 2px 8px;
            background: #451a03;
            color: #fb923c;
            border-radius: 4px;
            font-weight: 600;
        }

        .current-badge {
            font-size: 11px;
            padding: 2px 8px;
            background: #064e3b;
            color: #34d399;
            border-radius: 4px;
            font-weight: 600;
        }

        /* Endpoint card */
        .endpoint {
            background: #1a1d27;
            border: 1px solid #2d3148;
            border-radius: 10px;
            margin-bottom: 16px;
            overflow: hidden;
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            cursor: pointer;
            user-select: none;
            transition: background 0.15s;
        }

        .endpoint-header:hover { background: #1e2130; }

        .endpoint-title { font-size: 14px; font-weight: 500; color: #e2e8f0; }
        .endpoint-path  { font-size: 13px; color: #64748b; margin-left: auto; font-family: 'Courier New', monospace; }

        .chevron {
            margin-left: 8px;
            color: #475569;
            font-size: 12px;
            transition: transform 0.2s;
        }

        .endpoint-body {
            display: none;
            padding: 0 20px 20px;
            border-top: 1px solid #2d3148;
        }

        .endpoint-body.open { display: block; }

        .endpoint-desc {
            font-size: 13px;
            color: #94a3b8;
            padding: 12px 0;
        }

        /* Fields table */
        .fields-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #475569;
            margin: 12px 0 8px;
        }

        .fields-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .fields-table th {
            text-align: left;
            padding: 6px 10px;
            background: #0f1117;
            color: #64748b;
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .fields-table td {
            padding: 8px 10px;
            border-top: 1px solid #1e2130;
            color: #cbd5e1;
            vertical-align: top;
        }
        .field-name { font-family: 'Courier New', monospace; color: #c084fc; }
        .field-type { color: #60a5fa; font-size: 12px; }
        .req { color: #f87171; font-size: 11px; font-weight: 600; }
        .opt { color: #64748b; font-size: 11px; }

        /* Code block */
        .code-block {
            background: #0f1117;
            border: 1px solid #1e2130;
            border-radius: 8px;
            margin-top: 12px;
            overflow: hidden;
        }

        .code-block-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            background: #161925;
            border-bottom: 1px solid #1e2130;
        }

        .code-block-header span { font-size: 11px; color: #475569; font-weight: 600; }

        .copy-btn {
            font-size: 11px;
            color: #64748b;
            background: none;
            border: 1px solid #2d3148;
            border-radius: 4px;
            padding: 2px 8px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .copy-btn:hover { color: #e2e8f0; border-color: #475569; }

        pre {
            padding: 16px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.7;
            color: #94a3b8;
            white-space: pre;
        }

        /* Status badges */
        .statuses {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 10px;
        }

        .status-chip {
            font-size: 11px;
            padding: 3px 10px;
            border-radius: 4px;
            background: #1e2130;
            color: #94a3b8;
            font-family: 'Courier New', monospace;
            border: 1px solid #2d3148;
        }

        /* Info box */
        .info-box {
            background: #0c1a2e;
            border: 1px solid #1e3a5f;
            border-radius: 8px;
            padding: 14px 16px;
            font-size: 13px;
            color: #93c5fd;
            margin-top: 10px;
        }

        .info-box strong { color: #60a5fa; }

        @media (max-width: 768px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 24px 20px; }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <div class="sidebar-logo">
        <h2>Delivery API</h2>
        <p>Interactive Reference</p>
    </div>

    <div class="nav-section">Setup</div>
    <a href="#setup" class="nav-link active">Authentication</a>

    <div class="nav-section">V1 Endpoints</div>
    <a href="#v1-list"   class="nav-link"><span class="method-badge get">GET</span> List deliveries</a>
    <a href="#v1-create" class="nav-link"><span class="method-badge post">POST</span> Create delivery</a>
    <a href="#v1-show"   class="nav-link"><span class="method-badge get">GET</span> Show delivery</a>
    <a href="#v1-update" class="nav-link"><span class="method-badge put">PUT</span> Update status</a>
    <a href="#v1-delete" class="nav-link"><span class="method-badge del">DEL</span> Delete delivery</a>

    <div class="nav-section">V2 Endpoints</div>
    <a href="#v2-list"   class="nav-link"><span class="method-badge get">GET</span> List deliveries</a>
    <a href="#v2-create" class="nav-link"><span class="method-badge post">POST</span> Create delivery</a>
    <a href="#v2-show"   class="nav-link"><span class="method-badge get">GET</span> Show delivery</a>
    <a href="#v2-update" class="nav-link"><span class="method-badge put">PUT</span> Update delivery</a>
    <a href="#v2-delete" class="nav-link"><span class="method-badge del">DEL</span> Delete delivery</a>

    <div class="nav-section">Bulk Operations</div>
    <a href="#imports" class="nav-link"><span class="method-badge post">POST</span> CSV Import</a>
    <a href="#exports" class="nav-link"><span class="method-badge post">POST</span> CSV Export</a>
    <a href="#reports" class="nav-link"><span class="method-badge post">POST</span> Weekly Report</a>

    <div class="nav-section">Routes</div>
    <a href="#routes" class="nav-link">Route Management</a>

    <div class="nav-section">Real-time</div>
    <a href="#websockets" class="nav-link">WebSockets / Reverb</a>
</nav>

<!-- Main Content -->
<main class="main">

    <!-- Hero -->
    <div class="hero">
        <h1>Delivery Management API</h1>
        <p>RESTful API for managing deliveries, drivers, routes, CSV imports and async exports. Built with Laravel + Sanctum.</p>
        <div class="badge-row">
            <span class="tag tag-purple">Laravel 11</span>
            <span class="tag tag-green">Sanctum Auth</span>
            <span class="tag tag-blue">JSON / CSV</span>
            <span class="tag tag-purple">V1 + V2</span>
            <span class="tag tag-green">WebSockets / Reverb</span>
        </div>
    </div>

    <!-- Auth Setup -->
    <div id="setup" class="setup-box">
        <h3>Authentication — One-Time Setup</h3>
        <p style="font-size:13px;color:#64748b;margin-bottom:12px;">There is no public login endpoint. Generate a Sanctum token via Tinker and reuse it for all requests.</p>
        <div class="code-block">
            <div class="code-block-header">
                <span>SHELL</span>
                <button class="copy-btn" onclick="copyCode(this)">Copy</button>
            </div>
            <pre>php artisan tinker --execute="echo \App\Models\User::where('email','admin@example.com')->first()->createToken('test')->plainTextToken;"

# Then set these shell variables once:
BASE=http://localhost:8000/api
TOKEN=your_token_here</pre>
        </div>
        <div class="info-box" style="margin-top:12px;">
            <strong>Every request</strong> must include:<br>
            <code>-H "Authorization: Bearer $TOKEN"</code>&nbsp;&nbsp;
            <code>-H "Accept: application/json"</code>
        </div>
    </div>

    <!-- ── V1 DELIVERIES ── -->
    <div class="section">
        <div class="section-header">
            <h2>V1 Deliveries</h2>
            <span class="deprecated-badge">Deprecated</span>
            <span style="font-size:12px;color:#64748b;">Returns Deprecation + Sunset headers</span>
        </div>

        <!-- List -->
        <div id="v1-list" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge get">GET</span>
                <span class="endpoint-title">List Deliveries</span>
                <span class="endpoint-path">/api/v1/deliveries</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <p class="endpoint-desc">Returns a cursor-paginated list of deliveries for the authenticated user.</p>
                <div class="fields-label">Query Parameters</div>
                <table class="fields-table">
                    <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                    <tr><td class="field-name">limit</td><td class="field-type">integer</td><td class="opt">optional</td><td>Results per page (default: 15)</td></tr>
                    <tr><td class="field-name">cursor</td><td class="field-type">string</td><td class="opt">optional</td><td>Cursor from previous response for pagination</td></tr>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl "$BASE/v1/deliveries?limit=10&cursor=CURSOR_VALUE" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>

        <!-- Create -->
        <div id="v1-create" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge post">POST</span>
                <span class="endpoint-title">Create Delivery</span>
                <span class="endpoint-path">/api/v1/deliveries</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <p class="endpoint-desc">Creates a new delivery record.</p>
                <div class="fields-label">Request Body (JSON)</div>
                <table class="fields-table">
                    <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                    <tr><td class="field-name">recipient_name</td><td class="field-type">string</td><td class="req">required</td><td>Full name of the recipient</td></tr>
                    <tr><td class="field-name">recipient_phone</td><td class="field-type">string</td><td class="req">required</td><td>Recipient contact number</td></tr>
                    <tr><td class="field-name">pickup_address</td><td class="field-type">string</td><td class="req">required</td><td>Human-readable pickup location</td></tr>
                    <tr><td class="field-name">delivery_address</td><td class="field-type">string</td><td class="req">required</td><td>Human-readable drop-off location</td></tr>
                    <tr><td class="field-name">pickup_lat</td><td class="field-type">float</td><td class="opt">optional</td><td>Pickup latitude</td></tr>
                    <tr><td class="field-name">pickup_lng</td><td class="field-type">float</td><td class="opt">optional</td><td>Pickup longitude</td></tr>
                    <tr><td class="field-name">delivery_lat</td><td class="field-type">float</td><td class="opt">optional</td><td>Drop-off latitude</td></tr>
                    <tr><td class="field-name">delivery_lng</td><td class="field-type">float</td><td class="opt">optional</td><td>Drop-off longitude</td></tr>
                    <tr><td class="field-name">scheduled_at</td><td class="field-type">datetime</td><td class="opt">optional</td><td>ISO 8601 scheduled pickup time</td></tr>
                    <tr><td class="field-name">notes</td><td class="field-type">string</td><td class="opt">optional</td><td>Special instructions</td></tr>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X POST "$BASE/v1/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
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
  }'</pre>
                </div>
            </div>
        </div>

        <!-- Show -->
        <div id="v1-show" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge get">GET</span>
                <span class="endpoint-title">Show Delivery</span>
                <span class="endpoint-path">/api/v1/deliveries/{id}</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <p class="endpoint-desc">Retrieve a single delivery by its ID.</p>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl "$BASE/v1/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>

        <!-- Update -->
        <div id="v1-update" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge put">PUT</span>
                <span class="endpoint-title">Update Status</span>
                <span class="endpoint-path">/api/v1/deliveries/{id}</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <p class="endpoint-desc">Update the status (and optionally notes) of a delivery.</p>
                <div class="fields-label">Valid Statuses</div>
                <div class="statuses">
                    <span class="status-chip">pending</span>
                    <span class="status-chip">assigned</span>
                    <span class="status-chip">picked_up</span>
                    <span class="status-chip">in_transit</span>
                    <span class="status-chip">delivered</span>
                    <span class="status-chip">failed</span>
                    <span class="status-chip">cancelled</span>
                </div>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X PUT "$BASE/v1/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "in_transit", "notes": "On the way"}'</pre>
                </div>
            </div>
        </div>

        <!-- Delete -->
        <div id="v1-delete" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge del">DEL</span>
                <span class="endpoint-title">Delete Delivery</span>
                <span class="endpoint-path">/api/v1/deliveries/{id}</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <p class="endpoint-desc">Permanently delete a delivery record.</p>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X DELETE "$BASE/v1/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ── V2 DELIVERIES ── -->
    <div class="section">
        <div class="section-header">
            <h2>V2 Deliveries</h2>
            <span class="current-badge">Current</span>
        </div>

        <!-- List -->
        <div id="v2-list" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge get">GET</span>
                <span class="endpoint-title">List Deliveries</span>
                <span class="endpoint-path">/api/v2/deliveries</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <p class="endpoint-desc">Returns cursor-paginated deliveries with nested driver and user relationships included.</p>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl "$BASE/v2/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>

        <!-- Create -->
        <div id="v2-create" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge post">POST</span>
                <span class="endpoint-title">Create Delivery</span>
                <span class="endpoint-path">/api/v2/deliveries</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <p class="endpoint-desc">Create a delivery and optionally assign a driver immediately.</p>
                <div class="fields-label">Request Body (JSON)</div>
                <table class="fields-table">
                    <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                    <tr><td class="field-name">recipient_name</td><td class="field-type">string</td><td class="req">required</td><td>Full name of the recipient</td></tr>
                    <tr><td class="field-name">recipient_phone</td><td class="field-type">string</td><td class="req">required</td><td>Recipient contact number</td></tr>
                    <tr><td class="field-name">pickup_address</td><td class="field-type">string</td><td class="req">required</td><td>Human-readable pickup location</td></tr>
                    <tr><td class="field-name">delivery_address</td><td class="field-type">string</td><td class="req">required</td><td>Human-readable drop-off location</td></tr>
                    <tr><td class="field-name">driver_id</td><td class="field-type">integer</td><td class="opt">optional</td><td>Assign a driver at creation time</td></tr>
                    <tr><td class="field-name">scheduled_at</td><td class="field-type">datetime</td><td class="opt">optional</td><td>ISO 8601 scheduled pickup time</td></tr>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X POST "$BASE/v2/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "recipient_name": "Jane Smith",
    "recipient_phone": "01811000000",
    "pickup_address": "Banani 11, Dhaka",
    "delivery_address": "Mirpur 10, Dhaka",
    "driver_id": 2,
    "scheduled_at": "2026-06-29T09:00:00Z"
  }'</pre>
                </div>
            </div>
        </div>

        <!-- Show -->
        <div id="v2-show" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge get">GET</span>
                <span class="endpoint-title">Show Delivery</span>
                <span class="endpoint-path">/api/v2/deliveries/{id}</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl "$BASE/v2/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>

        <!-- Update -->
        <div id="v2-update" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge put">PUT</span>
                <span class="endpoint-title">Update Delivery</span>
                <span class="endpoint-path">/api/v2/deliveries/{id}</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <p class="endpoint-desc">Update status and/or reassign driver.</p>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X PUT "$BASE/v2/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"status": "assigned", "driver_id": 3}'</pre>
                </div>
            </div>
        </div>

        <!-- Delete -->
        <div id="v2-delete" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge del">DEL</span>
                <span class="endpoint-title">Delete Delivery</span>
                <span class="endpoint-path">/api/v2/deliveries/{id}</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X DELETE "$BASE/v2/deliveries/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ── IMPORTS ── -->
    <div class="section">
        <div class="section-header">
            <h2>CSV Import</h2>
            <span class="current-badge">Async</span>
        </div>

        <div id="imports" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge post">POST</span>
                <span class="endpoint-title">Upload CSV File</span>
                <span class="endpoint-path">/api/v1/imports</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <p class="endpoint-desc">Accepts a CSV file (up to 5,000 rows). Returns <strong>202 Accepted</strong> immediately with a job ID — processing happens in the background queue. Row failures do not abort the full import.</p>

                <div class="fields-label">CSV Columns</div>
                <table class="fields-table">
                    <tr><th>Column</th><th>Required</th><th>Description</th></tr>
                    <tr><td class="field-name">tracking_number</td><td class="req">required</td><td>Unique identifier for the delivery</td></tr>
                    <tr><td class="field-name">recipient_name</td><td class="req">required</td><td>Full name of the recipient</td></tr>
                    <tr><td class="field-name">recipient_phone</td><td class="req">required</td><td>Recipient phone number</td></tr>
                    <tr><td class="field-name">pickup_address</td><td class="req">required</td><td>Pickup location</td></tr>
                    <tr><td class="field-name">delivery_address</td><td class="req">required</td><td>Drop-off location</td></tr>
                    <tr><td class="field-name">status</td><td class="opt">optional</td><td>Defaults to <code>pending</code> if omitted</td></tr>
                </table>

                <div class="fields-label" style="margin-top:16px;">Example CSV</div>
                <div class="code-block">
                    <div class="code-block-header"><span>CSV</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>tracking_number,recipient_name,recipient_phone,pickup_address,delivery_address,status
TRK-001,John Doe,01711000000,"Gulshan 2, Dhaka","Dhanmondi 27, Dhaka",pending
TRK-002,Jane Smith,01811000000,"Banani 11, Dhaka","Mirpur 10, Dhaka",assigned</pre>
                </div>

                <div class="code-block" style="margin-top:12px;">
                    <div class="code-block-header"><span>CURL — Upload</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X POST "$BASE/v1/imports" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -F "file=@deliveries.csv"</pre>
                </div>

                <div class="code-block" style="margin-top:12px;">
                    <div class="code-block-header"><span>CURL — Poll Job Status</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl "$BASE/v1/imports/JOB_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>

                <div class="info-box">
                    <strong>Note:</strong> Requires <code>php artisan queue:work</code> to be running for background processing.
                </div>
            </div>
        </div>
    </div>

    <!-- ── EXPORTS ── -->
    <div class="section">
        <div class="section-header">
            <h2>CSV Export</h2>
            <span class="current-badge">Async</span>
        </div>

        <div id="exports" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge post">POST</span>
                <span class="endpoint-title">Trigger CSV Export</span>
                <span class="endpoint-path">/api/v1/exports/deliveries</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <p class="endpoint-desc">Triggers async CSV generation. Returns <strong>202 Accepted</strong> with an <code>export_key</code>. Poll the status endpoint for a download URL once complete.</p>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL — Trigger</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X POST "$BASE/v1/exports/deliveries" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
                <div class="code-block" style="margin-top:12px;">
                    <div class="code-block-header"><span>CURL — Poll Status</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl "$BASE/v1/exports/EXPORT_KEY/status" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ── REPORTS ── -->
    <div class="section">
        <div class="section-header">
            <h2>Weekly Report</h2>
            <span class="current-badge">Async</span>
        </div>

        <div id="reports" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge post">POST</span>
                <span class="endpoint-title">Generate Weekly Report</span>
                <span class="endpoint-path">/api/v1/reports/weekly</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <p class="endpoint-desc">Triggers async weekly report generation. Returns <strong>202 Accepted</strong> with a <code>report_key</code>.</p>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL — Trigger</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X POST "$BASE/v1/reports/weekly" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{}'</pre>
                </div>
                <div class="code-block" style="margin-top:12px;">
                    <div class="code-block-header"><span>CURL — Poll Status</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl "$BASE/v1/reports/REPORT_KEY/status" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ── ROUTES ── -->
    <div class="section">
        <div class="section-header">
            <h2>Route Management</h2>
            <span style="font-size:12px;color:#64748b;">Tenant-scoped, cache-aside</span>
        </div>

        <div id="routes" class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge get">GET</span>
                <span class="endpoint-title">List Routes</span>
                <span class="endpoint-path">/api/v1/routes</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl "$BASE/v1/routes" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge post">POST</span>
                <span class="endpoint-title">Create Route</span>
                <span class="endpoint-path">/api/v1/routes</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <div class="fields-label">Request Body (JSON)</div>
                <table class="fields-table">
                    <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                    <tr><td class="field-name">name</td><td class="field-type">string</td><td class="req">required</td><td>Route display name</td></tr>
                    <tr><td class="field-name">description</td><td class="field-type">string</td><td class="opt">optional</td><td>Route description</td></tr>
                    <tr><td class="field-name">waypoints</td><td class="field-type">array</td><td class="req">required</td><td>Array of <code>{lat, lng, address}</code> objects</td></tr>
                </table>
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X POST "$BASE/v1/routes" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Uttara → Motijheel Express",
    "description": "North-south commuter route",
    "waypoints": [
      {"lat": 23.8759, "lng": 90.3795, "address": "Uttara Sector 7, Dhaka"},
      {"lat": 23.7461, "lng": 90.3742, "address": "Dhanmondi 27, Dhaka"},
      {"lat": 23.7224, "lng": 90.4088, "address": "Motijheel, Dhaka"}
    ]
  }'</pre>
                </div>
            </div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge put">PUT</span>
                <span class="endpoint-title">Update Route</span>
                <span class="endpoint-path">/api/v1/routes/{id}</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X PUT "$BASE/v1/routes/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Route Name", "is_active": false}'</pre>
                </div>
            </div>
        </div>

        <div class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge del">DEL</span>
                <span class="endpoint-title">Delete Route</span>
                <span class="endpoint-path">/api/v1/routes/{id}</span>
                <span class="chevron">▶</span>
            </div>
            <div class="endpoint-body">
                <div class="code-block">
                    <div class="code-block-header"><span>CURL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>curl -X DELETE "$BASE/v1/routes/1" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- ── WEBSOCKETS ── -->
    <div id="websockets" class="section">
        <div class="section-header">
            <h2>Real-time WebSockets</h2>
            <span class="current-badge">Laravel Reverb</span>
        </div>

        <!-- What is Reverb -->
        <div class="setup-box" style="margin-bottom:20px;">
            <h3>What is Laravel Reverb?</h3>
            <p style="font-size:13px;color:#94a3b8;margin-bottom:10px;">
                <strong style="color:#c4b5fd;">Laravel Reverb</strong> is Laravel's official first-party WebSocket server.
                It runs as a standalone process alongside your app and handles persistent bi-directional connections between the server and connected clients.
            </p>
            <p style="font-size:13px;color:#94a3b8;margin-bottom:10px;">
                It is Pusher-protocol compatible — meaning any Pusher client SDK (Laravel Echo, pusher-js) works with it out of the box — but it runs <em>locally on your own server</em> with no external dependency or third-party account needed.
            </p>
            <p style="font-size:13px;color:#94a3b8;">
                Reverb replaces the need for Pusher's hosted service or a self-hosted Soketi server for development and production.
            </p>
        </div>

        <!-- Why we use it -->
        <div class="setup-box" style="margin-bottom:20px;border-color:#1e3a5f;">
            <h3 style="color:#60a5fa;">Why We Use Reverb Here</h3>
            <p style="font-size:13px;color:#94a3b8;margin-bottom:12px;">
                This API has two operations that are slow by nature — CSV imports and delivery status changes triggered by drivers in the field.
                Polling the status endpoint repeatedly is wasteful. Instead, the server pushes updates to the client the moment something changes.
            </p>
            <table class="fields-table">
                <tr><th>Trigger</th><th>Event Broadcast</th><th>Who receives it</th></tr>
                <tr>
                    <td style="color:#94a3b8;">CSV file uploaded via <code style="color:#c084fc;">POST /api/v1/imports</code></td>
                    <td><span class="status-chip">import.started</span></td>
                    <td style="color:#94a3b8;">The user who uploaded the file</td>
                </tr>
                <tr>
                    <td style="color:#94a3b8;">Delivery status updated via PUT endpoint</td>
                    <td><span class="status-chip">delivery.status.changed</span></td>
                    <td style="color:#94a3b8;">The driver assigned to that delivery</td>
                </tr>
            </table>
            <p style="font-size:13px;color:#64748b;margin-top:12px;">
                Both channels are <strong style="color:#93c5fd;">private</strong> — a user can only subscribe to their own channel, enforced by the channel authorization in <code>routes/channels.php</code>.
            </p>
        </div>

        <!-- Running Reverb -->
        <div class="endpoint">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge" style="background:#1a3a1a;color:#4ade80;">SRV</span>
                <span class="endpoint-title">Start the Reverb Server</span>
                <span class="endpoint-path">localhost:8080</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <p class="endpoint-desc">Run this in a separate terminal alongside <code>php artisan serve</code> and <code>php artisan queue:work</code>.</p>
                <div class="code-block">
                    <div class="code-block-header"><span>SHELL</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>php artisan reverb:start

# Expected output:
#   Starting Reverb server on 0.0.0.0:8080
#   WebSocket server started.</pre>
                </div>
                <div class="info-box" style="margin-top:12px;">
                    <strong>Three processes must run in parallel:</strong><br>
                    <code>php artisan serve</code> &nbsp;—&nbsp; HTTP API<br>
                    <code>php artisan queue:work</code> &nbsp;—&nbsp; Background jobs<br>
                    <code>php artisan reverb:start</code> &nbsp;—&nbsp; WebSocket server
                </div>
            </div>
        </div>

        <!-- Channels reference -->
        <div class="endpoint" style="margin-top:16px;">
            <div class="endpoint-header" onclick="toggle(this)">
                <span class="method-badge" style="background:#1e3a5f;color:#60a5fa;">WS</span>
                <span class="endpoint-title">Private Channels Reference</span>
                <span class="endpoint-path">ws://localhost:8080</span>
                <span class="chevron">▼</span>
            </div>
            <div class="endpoint-body open">
                <div class="fields-label">Channels</div>
                <table class="fields-table">
                    <tr><th>Channel</th><th>Event</th><th>Payload</th></tr>
                    <tr>
                        <td class="field-name">private-user.{userId}</td>
                        <td><span class="status-chip">import.started</span></td>
                        <td style="color:#94a3b8;font-size:12px;"><code>import_job_id</code>, <code>filename</code>, <code>status</code></td>
                    </tr>
                    <tr>
                        <td class="field-name">private-driver.{driverId}</td>
                        <td><span class="status-chip">delivery.status.changed</span></td>
                        <td style="color:#94a3b8;font-size:12px;"><code>delivery_id</code>, <code>tracking_number</code>, <code>previous_status</code>, <code>new_status</code>, <code>updated_at</code></td>
                    </tr>
                </table>

                <div class="fields-label" style="margin-top:16px;">Connect with Laravel Echo (JavaScript)</div>
                <div class="code-block">
                    <div class="code-block-header"><span>JS — npm install laravel-echo pusher-js</span><button class="copy-btn" onclick="copyCode(this)">Copy</button></div>
                    <pre>import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: 'rskv2od9uwobauyegcbq',       // REVERB_APP_KEY
    wsHost: 'localhost',
    wsPort: 8080,
    forceTLS: false,
    enabledTransports: ['ws'],
    authEndpoint: '/broadcasting/auth',  // Sanctum-authenticated
});

// Listen for import progress (subscribe as the logged-in user)
echo.private(`user.${userId}`)
    .listen('.import.started', (e) => {
        console.log('Import started:', e);
        // e.import_job_id, e.filename, e.status
    });

// Listen for delivery status changes (subscribe as the driver)
echo.private(`driver.${driverId}`)
    .listen('.delivery.status.changed', (e) => {
        console.log('Status changed:', e.tracking_number, e.new_status);
    });</pre>
                </div>

                <div class="info-box" style="margin-top:12px;">
                    <strong>Auth note:</strong> Private channels require a valid Sanctum session. Include <code>withCredentials: true</code> or pass the Bearer token in the Echo <code>auth.headers</code> option when connecting from a mobile/SPA client.
                </div>
            </div>
        </div>
    </div>

</main>

<script>
    function toggle(header) {
        const body = header.nextElementSibling;
        const chevron = header.querySelector('.chevron');
        const isOpen = body.classList.toggle('open');
        chevron.textContent = isOpen ? '▼' : '▶';
    }

    function copyCode(btn) {
        const pre = btn.closest('.code-block').querySelector('pre');
        navigator.clipboard.writeText(pre.textContent.trim()).then(() => {
            btn.textContent = 'Copied!';
            setTimeout(() => btn.textContent = 'Copy', 1500);
        });
    }

    // Highlight active nav link on scroll
    const sections = document.querySelectorAll('[id]');
    const navLinks = document.querySelectorAll('.nav-link');

    window.addEventListener('scroll', () => {
        let current = '';
        sections.forEach(s => {
            if (window.scrollY >= s.offsetTop - 100) current = s.id;
        });
        navLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === '#' + current);
        });
    });
</script>

</body>
</html>
