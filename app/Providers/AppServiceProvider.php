<?php

namespace App\Providers;

use App\Support\GatewaySetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.admin', function ($view): void {
            $baseUrl = GatewaySetting::baseUrl();
            $apiKey = GatewaySetting::appApiKey();
            $sessionId = GatewaySetting::sessionId();

            if ($baseUrl === '' || $apiKey === '' || $sessionId === '') {
                $view->with('headerGatewayStatus', [
                    'label' => 'Gateway belum lengkap',
                    'class' => 'badge-warn',
                ]);

                return;
            }

            $cacheKey = 'header_gateway_status_'.md5($baseUrl.'|'.$apiKey.'|'.$sessionId);
            $status = Cache::remember($cacheKey, now()->addSeconds(20), function () use ($baseUrl, $apiKey, $sessionId): array {
                try {
                    $response = Http::timeout(6)
                        ->acceptJson()
                        ->withHeaders(['x-api-key' => $apiKey])
                        ->get(rtrim($baseUrl, '/').'/sessions/'.urlencode($sessionId).'/status');

                    if (! $response->successful()) {
                        return [
                            'label' => 'Gateway error',
                            'class' => 'badge-warn',
                        ];
                    }

                    $sessionState = strtolower((string) $response->json('session.status', 'unknown'));
                    if ($sessionState === 'connected') {
                        return [
                            'label' => 'Gateway connected',
                            'class' => 'badge-success',
                        ];
                    }

                    if (in_array($sessionState, ['connecting', 'open'], true)) {
                        return [
                            'label' => 'Gateway connecting',
                            'class' => 'badge-info',
                        ];
                    }

                    return [
                        'label' => 'Gateway '.$sessionState,
                        'class' => 'badge-warn',
                    ];
                } catch (\Throwable) {
                    return [
                        'label' => 'Gateway offline',
                        'class' => 'badge-warn',
                    ];
                }
            });

            $view->with('headerGatewayStatus', $status);
        });
    }
}
