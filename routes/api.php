<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExportController;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'service' => 'Clicks Service'
    ]);
});

// Webhook endpoints for receiving clicks
Route::prefix('webhook')->group(function () {
    Route::post('/clicks', [WebhookController::class, 'receiveClick']);
    Route::post('/clicks/batch', [WebhookController::class, 'receiveBatchClicks']);
});

// Report endpoints
Route::prefix('reports')->group(function () {
    Route::get('/aggregated', [ReportController::class, 'getAggregatedReport']);
    Route::get('/summary', [ReportController::class, 'getSummary']);
});

// Export endpoints
Route::prefix('export')->group(function () {
    Route::post('/forward', [ExportController::class, 'forward']);
    Route::get('/status', [ExportController::class, 'status']);
});
