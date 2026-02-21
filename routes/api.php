<?php

use App\Http\Controllers\Api\Admin\ClientController;
use App\Http\Controllers\Api\Admin\MessageTemplateController;
use App\Http\Controllers\Api\Admin\PushController;
use App\Http\Controllers\Api\Admin\SignalController;
use App\Http\Controllers\Api\Admin\TierController;
use App\Http\Controllers\Api\Admin\WaBlastController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientSignalController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/fcm-token', [AuthController::class, 'updateFcmToken']);

    Route::get('/client/signals', [ClientSignalController::class, 'index']);

    Route::middleware('admin')->prefix('admin')->name('api.admin.')->group(function () {
        Route::apiResource('tiers', TierController::class);
        Route::apiResource('clients', ClientController::class);
        Route::apiResource('signals', SignalController::class);
        Route::apiResource('message-templates', MessageTemplateController::class);
        Route::post('push/broadcast', [PushController::class, 'broadcast']);
        Route::get('wa-blast-targets', [WaBlastController::class, 'index']);
        Route::get('wa-blast/birthdays', [WaBlastController::class, 'birthdayTargets']);
        Route::get('wa-blast/holidays', [WaBlastController::class, 'holidayTargets']);
        Route::post('wa-blast/preview', [WaBlastController::class, 'preview']);
        Route::post('wa-blast/manual-send', [WaBlastController::class, 'manualSend']);
    });
});
