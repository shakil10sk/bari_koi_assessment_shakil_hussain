<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ExportDeliveriesToCsv;
use App\Jobs\GenerateDeliveryReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function generateWeeklyReport(Request $request): JsonResponse
    {
        $reportKey = Str::uuid()->toString();

        Cache::put("report:{$reportKey}:status", 'queued', now()->addHour());

        GenerateDeliveryReport::dispatch($reportKey, $request->only(['user_id', 'tenant_id']));

        return response()->json([
            'message'    => 'Report `generation` queued.',
            'report_key' => $reportKey,
            'status_url' => route('api.v1.reports.status', ['key' => $reportKey]),
        ], 202);
    }

    public function reportStatus(string $key): JsonResponse
    {
        $status = Cache::get("report:{$key}:status");
        $result = Cache::get("report:{$key}");

        if ($status === null && $result === null) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        if ($result && $result['status'] === 'ready') {
            return response()->json($result);
        }

        return response()->json(['status' => $status ?? 'pending']);
    }

    public function exportCsv(Request $request): JsonResponse
    {
        $exportKey = Str::uuid()->toString();

        Cache::put("export:{$exportKey}", ['status' => 'queued'], now()->addHour());

        ExportDeliveriesToCsv::dispatch(auth()->id(), $exportKey);

        return response()->json([
            'message'    => 'Export queued.',
            'export_key' => $exportKey,
            'status_url' => route('api.v1.exports.status', ['key' => $exportKey]),
        ], 202);
    }

    public function exportStatus(string $key): JsonResponse
    {
        $export = Cache::get("export:{$key}");

        if (! $export) {
            return response()->json(['error' => 'Export not found or expired'], 404);
        }

        return response()->json($export);
    }

    public function exportDownload(string $key): \Symfony\Component\HttpFoundation\StreamedResponse|JsonResponse
    {
        $export = Cache::get("export:{$key}");

        if (! $export || $export['status'] !== 'ready') {
            return response()->json(['error' => 'Export not ready or expired'], 404);
        }

        return Storage::disk('local')->download($export['path'], "deliveries-export.csv");
    }
}
