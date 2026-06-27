<?php

namespace App\Jobs;

use App\Models\Delivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GenerateDeliveryReport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public readonly string $reportKey,
        public readonly array $filters = [],
    ) {}

    public function handle(): void
    {
        Cache::put("report:{$this->reportKey}:status", 'processing', now()->addHour());

        $data = $this->buildWeeklyReport();

        Cache::put("report:{$this->reportKey}", [
            'status'     => 'ready',
            'data'       => $data,
            'generated_at' => now()->toISOString(),
        ], now()->addHour());
    }

    private function buildWeeklyReport(): array
    {
        return DB::table('deliveries')
            ->select([
                DB::raw("date_trunc('week', created_at) AS week"),
                DB::raw('COUNT(*) AS total_deliveries'),
                DB::raw("ROUND(100.0 * SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 2) AS success_rate"),
                DB::raw("ROUND(AVG(EXTRACT(EPOCH FROM (delivered_at - created_at)) / 3600)::numeric, 2) AS avg_delivery_hours"),
            ])
            ->where('created_at', '>=', now()->subMonths(3))
            ->whereNull('deleted_at')
            ->groupByRaw("date_trunc('week', created_at)")
            ->orderByRaw("date_trunc('week', created_at)")
            ->get()
            ->toArray();
    }
}
