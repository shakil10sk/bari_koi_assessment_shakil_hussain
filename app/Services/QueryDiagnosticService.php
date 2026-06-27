<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QueryDiagnosticService
{
    public function explainSlowQuery(): array
    {
        return DB::select("
            EXPLAIN (ANALYZE, BUFFERS, FORMAT TEXT)
            SELECT d.id, d.tracking_number, d.status, d.created_at,
                   u.name AS user_name, u.email,
                   dl.to_status AS latest_status, dl.created_at AS last_log_at
            FROM deliveries d
            INNER JOIN users u ON u.id = d.user_id
            LEFT JOIN delivery_logs dl ON dl.id = (
                SELECT id FROM delivery_logs
                WHERE delivery_id = d.id
                ORDER BY created_at DESC
                LIMIT 1
            )
            WHERE d.user_id = 1
              AND d.status = 'in_transit'
              AND d.created_at BETWEEN NOW() - INTERVAL '30 days' AND NOW()
            ORDER BY d.created_at DESC
        ");
    }

    public function checkMissingIndexes(): array
    {
        return DB::select("
            SELECT schemaname, tablename, attname, n_distinct, correlation
            FROM pg_stats
            WHERE tablename IN ('deliveries', 'users', 'delivery_logs')
            ORDER BY tablename, attname
        ");
    }

    public function checkBlockingQueries(): array
    {
        return DB::select("
            SELECT pid, now() - pg_stat_activity.query_start AS duration,
                   query, state, wait_event_type, wait_event
            FROM pg_stat_activity
            WHERE (now() - pg_stat_activity.query_start) > INTERVAL '5 seconds'
              AND state = 'active'
        ");
    }

    // The correlated subquery in explainSlowQuery runs once per delivery row.
    // Replacing it with DISTINCT ON or a lateral join cuts query time significantly on large tables.
    public function optimisedQuery(): array
    {
        return DB::select("
            SELECT DISTINCT ON (d.id)
                d.id, d.tracking_number, d.status, d.created_at,
                u.name AS user_name, u.email,
                dl.to_status AS latest_status, dl.created_at AS last_log_at
            FROM deliveries d
            INNER JOIN users u ON u.id = d.user_id
            LEFT JOIN delivery_logs dl ON dl.delivery_id = d.id
            WHERE d.user_id = :userId
              AND d.status = 'in_transit'
              AND d.created_at >= NOW() - INTERVAL '30 days'
            ORDER BY d.id, dl.created_at DESC
        ", ['userId' => 1]);
    }
}
