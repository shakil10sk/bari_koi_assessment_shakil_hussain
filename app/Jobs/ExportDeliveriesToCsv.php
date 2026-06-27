<?php

namespace App\Jobs;

use App\Models\Delivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use League\Csv\Writer;
use SplTempFileObject;

class ExportDeliveriesToCsv implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(
        public readonly int $userId,
        public readonly string $exportKey,
    ) {}

    public function handle(): void
    {
        $filename = "exports/deliveries-{$this->userId}-" . now()->format('Ymd-His') . '.csv';
        $tmpFile = tempnam(sys_get_temp_dir(), 'delivery_export_');

        try {
            $writer = Writer::createFromPath($tmpFile, 'w+');
            $writer->insertOne(['ID', 'Tracking Number', 'Status', 'Recipient', 'Phone',
                'Pickup Address', 'Delivery Address', 'Created At']);

            Delivery::where('user_id', $this->userId)
                ->select(['id', 'tracking_number', 'status', 'recipient_name', 'recipient_phone',
                    'pickup_address', 'delivery_address', 'created_at'])
                ->orderBy('id')
                ->chunk(500, function ($deliveries) use ($writer) {
                    foreach ($deliveries as $d) {
                        $writer->insertOne([
                            $d->id, $d->tracking_number, $d->status, $d->recipient_name,
                            $d->recipient_phone, $d->pickup_address, $d->delivery_address,
                            $d->created_at->toDateTimeString(),
                        ]);
                    }
                });

            Storage::disk('local')->put($filename, file_get_contents($tmpFile));

            // temporaryUrl() is S3-only; use a signed route for the local driver
            $signedUrl = URL::temporarySignedRoute(
                'api.v1.exports.download',
                now()->addHour(),
                ['key' => $this->exportKey]
            );

            Cache::put("export:{$this->exportKey}", [
                'status' => 'ready',
                'url'    => $signedUrl,
                'path'   => $filename,
            ], now()->addHour());
        } finally {
            @unlink($tmpFile);
        }
    }
}
