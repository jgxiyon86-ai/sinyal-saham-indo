<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\ClientPageController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GatewaySettingPageController;
use App\Http\Controllers\Web\MessageTemplatePageController;
use App\Http\Controllers\Web\PushPageController;
use App\Http\Controllers\Web\SignalPageController;
use App\Http\Controllers\Web\SignalWaBlastPageController;
use App\Http\Controllers\Web\TierPageController;
use App\Http\Controllers\Web\WaBlastPageController;
use App\Http\Controllers\Web\LoginThemePageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::middleware(['auth', 'admin.web'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/clients', [ClientPageController::class, 'index'])->name('clients.page');
    Route::get('/clients/create', [ClientPageController::class, 'create'])->name('clients.create');
    Route::get('/clients/{client}/edit', [ClientPageController::class, 'edit'])->name('clients.edit');
    Route::post('/clients', [ClientPageController::class, 'store'])->name('clients.store');
    Route::put('/clients/{client}', [ClientPageController::class, 'update'])->name('clients.update');
    Route::post('/clients/{client}/send-credentials', [ClientPageController::class, 'sendCredentials'])->name('clients.send-credentials');
    Route::delete('/clients/{client}', [ClientPageController::class, 'destroy'])->name('clients.destroy');

    Route::get('/tiers', [TierPageController::class, 'index'])->name('tiers.page');
    Route::get('/tiers/create', [TierPageController::class, 'create'])->name('tiers.create');
    Route::get('/tiers/{tier}/edit', [TierPageController::class, 'edit'])->name('tiers.edit');
    Route::post('/tiers', [TierPageController::class, 'store'])->name('tiers.store');
    Route::put('/tiers/{tier}', [TierPageController::class, 'update'])->name('tiers.update');
    Route::delete('/tiers/{tier}', [TierPageController::class, 'destroy'])->name('tiers.destroy');

    Route::get('/signals', [SignalPageController::class, 'index'])->name('signals.page');
    Route::get('/signals/create', [SignalPageController::class, 'create'])->name('signals.create');
    Route::get('/signals/{signal}/edit', [SignalPageController::class, 'edit'])->name('signals.edit');
    Route::post('/signals', [SignalPageController::class, 'store'])->name('signals.store');
    Route::put('/signals/{signal}', [SignalPageController::class, 'update'])->name('signals.update');
    Route::delete('/signals/{signal}', [SignalPageController::class, 'destroy'])->name('signals.destroy');

    Route::get('/message-templates', [MessageTemplatePageController::class, 'index'])->name('templates.page');
    Route::get('/message-templates/create', [MessageTemplatePageController::class, 'create'])->name('templates.create');
    Route::get('/message-templates/{messageTemplate}/edit', [MessageTemplatePageController::class, 'edit'])->name('templates.edit');
    Route::post('/message-templates', [MessageTemplatePageController::class, 'store'])->name('templates.store');
    Route::put('/message-templates/{messageTemplate}', [MessageTemplatePageController::class, 'update'])->name('templates.update');
    Route::delete('/message-templates/{messageTemplate}', [MessageTemplatePageController::class, 'destroy'])->name('templates.destroy');

    Route::get('/wa-blast', [WaBlastPageController::class, 'index'])->name('wa-blast.page');
    Route::post('/wa-blast/preview', [WaBlastPageController::class, 'preview'])->name('wa-blast.preview');
    Route::post('/wa-blast/send', [WaBlastPageController::class, 'send'])->name('wa-blast.send');
    Route::post('/wa-blast/manual-send', [WaBlastPageController::class, 'manualSend'])->name('wa-blast.manual-send');
    Route::post('/wa-blast/upload-image', [WaBlastPageController::class, 'uploadImage'])->name('wa-blast.upload-image');
    Route::get('/wa-blast-sinyal', [SignalWaBlastPageController::class, 'index'])->name('signal-wa-blast.page');
    Route::get('/wa-blast-sinyal/preview', fn () => redirect()->route('signal-wa-blast.page'));
    Route::get('/wa-blast-sinyal/send', fn () => redirect()->route('signal-wa-blast.page'));
    Route::post('/wa-blast-sinyal/preview', [SignalWaBlastPageController::class, 'preview'])->name('signal-wa-blast.preview');
    Route::post('/wa-blast-sinyal/send', [SignalWaBlastPageController::class, 'send'])->name('signal-wa-blast.send');
    Route::get('/push-broadcast', [PushPageController::class, 'index'])->name('push.page');
    Route::post('/push-broadcast', [PushPageController::class, 'send'])->name('push.send');
    Route::get('/login-theme', [LoginThemePageController::class, 'index'])->name('login-theme.page');
    Route::post('/login-theme/login', [LoginThemePageController::class, 'updateLogin'])->name('login-theme.login.update');
    Route::post('/login-theme/panel', [LoginThemePageController::class, 'updatePanel'])->name('login-theme.panel.update');
    Route::get('/wa-config', [GatewaySettingPageController::class, 'index'])->name('gateway-settings.page');
    Route::post('/wa-config', [GatewaySettingPageController::class, 'update'])->name('gateway-settings.update');
    Route::post('/wa-config/test', [GatewaySettingPageController::class, 'test'])->name('gateway-settings.test');
    Route::get('/gateway-settings', [GatewaySettingPageController::class, 'index']);
    Route::post('/gateway-settings', [GatewaySettingPageController::class, 'update']);
    Route::post('/gateway-settings/test', [GatewaySettingPageController::class, 'test']);

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
