<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Support\GatewaySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class GatewaySettingPageController extends Controller
{
    public function index(): View
    {
        $sessionStatus = $this->fetchSessionStatus(
            GatewaySetting::baseUrl(),
            GatewaySetting::appApiKey(),
            GatewaySetting::sessionId()
        );

        return view('admin.gateway-settings', [
            'settings' => [
                'gateway_base_url' => GatewaySetting::baseUrl(),
                'gateway_panel_url' => GatewaySetting::panelUrl(),
                'gateway_app_id' => GatewaySetting::appId(),
                'gateway_app_api_key' => GatewaySetting::appApiKey(),
                'gateway_session_id' => GatewaySetting::sessionId(),
                'wa_birthday_auto_time' => GatewaySetting::birthdayAutoTime(),
            ],
            'session_status' => $sessionStatus,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gateway_base_url' => ['required', 'url', 'max:255'],
            'gateway_panel_url' => ['nullable', 'url', 'max:255'],
            'gateway_app_id' => ['nullable', 'string', 'max:120'],
            'gateway_app_api_key' => ['nullable', 'string', 'max:255'],
            'gateway_session_id' => ['nullable', 'string', 'max:255'],
            'wa_birthday_auto_time' => ['nullable', 'date_format:H:i'],
        ]);

        AppSetting::setValue('gateway_base_url', trim($data['gateway_base_url']));
        AppSetting::setValue('gateway_panel_url', trim((string) ($data['gateway_panel_url'] ?? '')));
        AppSetting::setValue('gateway_app_id', trim((string) ($data['gateway_app_id'] ?? '')));
        AppSetting::setValue('gateway_app_api_key', trim((string) ($data['gateway_app_api_key'] ?? '')));
        AppSetting::setValue('gateway_session_id', trim((string) ($data['gateway_session_id'] ?? '')));
        AppSetting::setValue('wa_birthday_auto_time', trim((string) ($data['wa_birthday_auto_time'] ?? '08:00')));

        return redirect()->route('gateway-settings.page')->with('status', 'Pengaturan gateway berhasil disimpan.');
    }

    public function test(Request $request): RedirectResponse
    {
        $baseUrl = GatewaySetting::baseUrl();
        $panelUrl = GatewaySetting::panelUrl();
        $appApiKey = GatewaySetting::appApiKey();
        $sessionId = GatewaySetting::sessionId();

        $result = [
            'hub_health' => 'fail',
            'hub_session' => 'skip',
            'panel_login' => 'skip',
            'detail' => [],
        ];

        try {
            $health = Http::timeout(10)->acceptJson()->get(rtrim($baseUrl, '/').'/health');
            if ($health->successful() && (bool) $health->json('ok')) {
                $result['hub_health'] = 'ok';
            } else {
                $result['detail'][] = 'Hub health gagal: HTTP '.$health->status();
            }
        } catch (\Throwable $e) {
            $result['detail'][] = 'Hub health error: '.$e->getMessage();
        }

        if ($appApiKey !== '' && $sessionId !== '') {
            $sessionCheck = $this->fetchSessionStatus($baseUrl, $appApiKey, $sessionId);
            if (($sessionCheck['state'] ?? '') === 'connected') {
                $result['hub_session'] = 'ok';
            } elseif (($sessionCheck['state'] ?? '') === 'skip') {
                $result['hub_session'] = 'skip';
            } else {
                $result['hub_session'] = 'fail';
            }
            if (!empty($sessionCheck['detail'])) {
                $result['detail'][] = $sessionCheck['detail'];
            }
        } else {
            $result['detail'][] = 'Session check dilewati: API key/session belum diisi.';
        }

        if ($panelUrl !== '') {
            try {
                $panel = Http::timeout(10)->get(rtrim($panelUrl, '/').'/login');
                if ($panel->successful() || in_array($panel->status(), [301, 302], true)) {
                    $result['panel_login'] = 'ok';
                } else {
                    $result['panel_login'] = 'fail';
                    $result['detail'][] = 'Panel login check gagal: HTTP '.$panel->status();
                }
            } catch (\Throwable $e) {
                $result['panel_login'] = 'fail';
                $result['detail'][] = 'Panel login check error: '.$e->getMessage();
            }
        } else {
            $result['detail'][] = 'Panel check dilewati: URL panel belum diisi.';
        }

        $okAll = $result['hub_health'] === 'ok'
            && in_array($result['hub_session'], ['ok', 'skip'], true)
            && in_array($result['panel_login'], ['ok', 'skip'], true);

        return redirect()
            ->route('gateway-settings.page')
            ->with('status', $okAll ? 'Koneksi gateway berhasil diuji.' : 'Tes koneksi gateway ada yang gagal.')
            ->with('gateway_test', $result);
    }

    private function fetchSessionStatus(string $baseUrl, string $appApiKey, string $sessionId): array
    {
        if ($appApiKey === '' || $sessionId === '') {
            return [
                'state' => 'skip',
                'label' => 'Belum diisi',
                'detail' => 'API key / Session ID belum diisi.',
            ];
        }

        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->withHeaders(['x-api-key' => $appApiKey])
                ->get(rtrim($baseUrl, '/').'/sessions/'.urlencode($sessionId).'/status');

            if (! $response->successful()) {
                return [
                    'state' => 'error',
                    'label' => 'Error',
                    'detail' => 'Hub session status gagal: HTTP '.$response->status(),
                ];
            }

            $status = (string) strtolower((string) $response->json('session.status', 'unknown'));
            return [
                'state' => $status,
                'label' => strtoupper($status),
                'detail' => 'Session: '.(string) $response->json('session.sessionId', $sessionId),
            ];
        } catch (\Throwable $e) {
            return [
                'state' => 'error',
                'label' => 'Error',
                'detail' => 'Hub session status error: '.$e->getMessage(),
            ];
        }
    }
}
