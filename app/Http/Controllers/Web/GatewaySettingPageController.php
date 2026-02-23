<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Support\GatewaySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GatewaySettingPageController extends Controller
{
    public function index(): View
    {
        return view('admin.gateway-settings', [
            'settings' => [
                'gateway_base_url' => GatewaySetting::baseUrl(),
                'gateway_app_api_key' => GatewaySetting::appApiKey(),
                'gateway_session_id' => GatewaySetting::sessionId(),
                'wa_birthday_auto_time' => GatewaySetting::birthdayAutoTime(),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gateway_base_url' => ['required', 'url', 'max:255'],
            'gateway_app_api_key' => ['nullable', 'string', 'max:255'],
            'gateway_session_id' => ['nullable', 'string', 'max:255'],
            'wa_birthday_auto_time' => ['nullable', 'date_format:H:i'],
        ]);

        AppSetting::setValue('gateway_base_url', trim($data['gateway_base_url']));
        AppSetting::setValue('gateway_app_api_key', trim((string) ($data['gateway_app_api_key'] ?? '')));
        AppSetting::setValue('gateway_session_id', trim((string) ($data['gateway_session_id'] ?? '')));
        AppSetting::setValue('wa_birthday_auto_time', trim((string) ($data['wa_birthday_auto_time'] ?? '08:00')));

        return redirect()->route('gateway-settings.page')->with('status', 'Pengaturan gateway berhasil disimpan.');
    }
}

