<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\V1\DeliveryController as V1DeliveryController;
use App\Http\Controllers\Api\V1\DeliveryRouteController;
use App\Http\Controllers\Api\V1\ImportController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V2\DeliveryController as V2DeliveryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn (Request $request) => $request->user());
});

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'App\Http\Middleware\TenantMiddleware', 'throttle:api', 'App\Http\Middleware\DeprecatedApiMiddleware'])
    ->group(function () {
        Route::apiResource('deliveries', V1DeliveryController::class);

        Route::post('imports', [ImportController::class, 'store'])->name('imports.store');
        Route::get('imports/{importJob}', [ImportController::class, 'show'])->name('imports.show');

        Route::post('reports/weekly', [ReportController::class, 'generateWeeklyReport'])->name('reports.weekly');
        Route::get('reports/{key}/status', [ReportController::class, 'reportStatus'])->name('reports.status');

        Route::post('exports/deliveries', [ReportController::class, 'exportCsv'])->name('exports.deliveries');
        Route::get('exports/{key}/status', [ReportController::class, 'exportStatus'])->name('exports.status');
        Route::get('exports/{key}/download', [ReportController::class, 'exportDownload'])->name('exports.download')->middleware('signed');

        Route::apiResource('routes', DeliveryRouteController::class);
    });

Route::prefix('v2')
    ->name('api.v2.')
    ->middleware(['auth:sanctum', 'App\Http\Middleware\TenantMiddleware', 'throttle:api'])
    ->group(function () {
        Route::apiResource('deliveries', V2DeliveryController::class);
    });
