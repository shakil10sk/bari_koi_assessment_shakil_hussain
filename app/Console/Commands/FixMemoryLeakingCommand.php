<?php

namespace App\Console\Commands;

use App\Models\Delivery;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('deliveries:process-large {--chunk=500}')]
#[Description('Process 100k delivery records without memory exhaustion')]
class FixMemoryLeakingCommand extends Command
{
    public function handle(): int
    {
        DB::disableQueryLog();

        $chunkSize = (int) $this->option('chunk');
        $processed = 0;

        Delivery::query()
            ->select(['id', 'status', 'tracking_number', 'user_id'])
            ->orderBy('id')
            ->chunk($chunkSize, function ($deliveries) use (&$processed) {
                foreach ($deliveries as $delivery) {
                    $this->processRecord($delivery);
                    $processed++;
                }

                unset($deliveries);

                if ($processed % 10000 === 0) {
                    $this->line("Processed {$processed} | Memory: " . round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB');
                }
            });

        $this->info("Done. Processed {$processed} records.");

        return self::SUCCESS;
    }

    private function processRecord(Delivery $delivery): void
    {
        Log::debug("Processing delivery {$delivery->id}");
    }
}
