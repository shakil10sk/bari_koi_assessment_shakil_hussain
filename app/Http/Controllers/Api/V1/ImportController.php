<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\DeliveryImportStarted;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessDeliveryImport;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $path = $request->file('file')->store('imports');

        $importJob = ImportJob::create([
            'user_id'    => auth()->id(),
            'filename'   => $request->file('file')->getClientOriginalName(),
            'disk'       => 'local',
            'path'       => $path,
            'status'     => 'pending',
            'total_rows' => 0,
        ]);

        ProcessDeliveryImport::dispatch($importJob);
        DeliveryImportStarted::dispatch($importJob);

        return response()->json([
            'message'       => 'Import queued successfully.',
            'import_job_id' => $importJob->id,
            'status_url'    => route('api.v1.imports.show', $importJob),
        ], 202);
    }

    public function show(ImportJob $importJob): JsonResponse
    {
        $this->authorize('view', $importJob);

        return response()->json([
            'id'              => $importJob->id,
            'status'          => $importJob->status,
            'progress'        => $importJob->progressPercentage(),
            'total_rows'      => $importJob->total_rows,
            'processed_rows'  => $importJob->processed_rows,
            'failed_rows'     => $importJob->failed_rows,
            'errors'          => $importJob->errors,
            'started_at'      => $importJob->started_at?->toISOString(),
            'completed_at'    => $importJob->completed_at?->toISOString(),
        ]);
    }
}
