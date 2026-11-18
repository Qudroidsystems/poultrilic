<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\LivestockApiController;
use App\Http\Controllers\Api\V1\PoultryApiController;
use App\Http\Controllers\Api\V1\ReportApiController;
use Illuminate\Support\Facades\Route;

/*
| Mobile API v1 (Sanctum tokens). Answers are {"success", "message", "data", "meta"}; errors carry "message"
| and, for validation, "errors" keyed by field. See docs/API.md.
*/
Route::prefix('v1')->group(function () {
    Route::get('app/config', [AuthController::class, 'config']);
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::post('auth/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

        Route::get('dashboard', [PoultryApiController::class, 'dashboard']);
        Route::get('flocks', [PoultryApiController::class, 'flocks']);
        Route::get('flocks/{flock}', [PoultryApiController::class, 'flock']);
        Route::get('flocks/{flock}/entries', [PoultryApiController::class, 'entries']);
        Route::post('flocks/{flock}/entries', [PoultryApiController::class, 'saveEntry']);
        Route::delete('entries/{entry}', [PoultryApiController::class, 'deleteEntry']);

        Route::get('livestock/types', [LivestockApiController::class, 'types']);
        Route::get('livestock/units/{unit}', [LivestockApiController::class, 'showUnit']);
        Route::get('livestock/units/{unit}/records', [LivestockApiController::class, 'records']);
        Route::post('livestock/units/{unit}/records', [LivestockApiController::class, 'saveRecord']);
        Route::delete('livestock/records/{record}', [LivestockApiController::class, 'deleteRecord']);

        Route::get('reports', [ReportApiController::class, 'index']);
        Route::get('reports/{report}', [ReportApiController::class, 'show']);
    });
});
