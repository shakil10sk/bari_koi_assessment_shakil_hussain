<?php

namespace App\Jobs;

use App\Models\Delivery;
use App\Models\ImportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\UnavailableStream;
use Throwable;

class ProcessDeliveryImport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public readonly ImportJob $importJob) {}

    public function handle(): void
    {
        $this->importJob->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $stream = Storage::disk($this->importJob->disk)
                ->readStream($this->importJob->path);

            $csv = Reader::createFromStream($stream);
            $csv->setHeaderOffset(0);

            $errors = [];
            $processed = 0;

            foreach ($csv->getRecords() as $offset => $row) {
                try {
                    $this->importRow($row);
                    $processed++;
                } catch (Throwable $e) {
                    $errors[] = ['row' => $offset + 2, 'error' => $e->getMessage()];
                    $this->importJob->increment('failed_rows');
                }

                if ($processed % 100 === 0) {
                    $this->importJob->update(['processed_rows' => $processed]);
                }
            }

            $this->importJob->update([
                'status'         => 'completed',
                'processed_rows' => $processed,
                'errors'         => $errors,
                'completed_at'   => now(),
            ]);
        } catch (UnavailableStream $e) {
            $this->importJob->update(['status' => 'failed', 'errors' => [['error' => $e->getMessage()]]]);
        }
    }

    private function importRow(array $row): void
    {
        Delivery::create([
            'user_id'          => $this->importJob->user_id,
            'tracking_number'  => $row['tracking_number'] ?? throw new \InvalidArgumentException('Missing tracking_number'),
            'status'           => $row['status'] ?: 'pending',
            'recipient_name'   => $row['recipient_name'] ?? throw new \InvalidArgumentException('Missing recipient_name'),
            'recipient_phone'  => $row['recipient_phone'] ?? throw new \InvalidArgumentException('Missing recipient_phone'),
            'pickup_address'   => $row['pickup_address'] ?? throw new \InvalidArgumentException('Missing pickup_address'),
            'delivery_address' => $row['delivery_address'] ?? throw new \InvalidArgumentException('Missing delivery_address'),
        ]);
    }
}
