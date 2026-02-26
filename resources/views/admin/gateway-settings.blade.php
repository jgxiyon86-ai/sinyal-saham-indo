@extends('layouts.admin')

@section('title', 'Pengaturan Gateway')

@section('content')
    <h1 style="margin-top: 0;">Pengaturan Gateway WA</h1>
    <p style="margin-top: 0; color: var(--muted);">Isi di sini agar aplikasi langsung pakai koneksi ALIMA Gateway tanpa edit file <code>.env</code>. API key dan session diambil dari <strong>panelhub.cuanholic.com</strong>.</p>

    <form method="POST" action="{{ route('gateway-settings.update') }}">
        @csrf
        <div class="panel">
            <div class="field-grid">
                <div>
                    <label for="gateway_base_url">Gateway Base URL</label>
                    <input id="gateway_base_url" name="gateway_base_url" type="url" required
                           value="{{ old('gateway_base_url', $settings['gateway_base_url']) }}"
                           placeholder="https://hubku.cuanholic.com">
                </div>
                <div>
                    <label for="gateway_panel_url">Panel URL</label>
                    <input id="gateway_panel_url" name="gateway_panel_url" type="url"
                           value="{{ old('gateway_panel_url', $settings['gateway_panel_url'] ?? '') }}"
                           placeholder="https://panelhub.cuanholic.com">
                </div>
                <div>
                    <label for="gateway_app_id">App ID (Panel)</label>
                    <input id="gateway_app_id" name="gateway_app_id" type="text"
                           value="{{ old('gateway_app_id', $settings['gateway_app_id'] ?? '') }}"
                           placeholder="contoh: sinyal-saham-indo">
                </div>
                <div>
                    <label for="gateway_app_api_key">Gateway App API Key</label>
                    <input id="gateway_app_api_key" name="gateway_app_api_key" type="text"
                           value="{{ old('gateway_app_api_key', $settings['gateway_app_api_key']) }}"
                           placeholder="paste app api key">
                </div>
                <div>
                    <label for="gateway_session_id">Gateway Session ID</label>
                    <input id="gateway_session_id" name="gateway_session_id" type="text"
                           value="{{ old('gateway_session_id', $settings['gateway_session_id']) }}"
                           placeholder="contoh: wa6289952xxxx">
                </div>
                <div>
                    <label for="wa_birthday_auto_time">Jam Auto WA Ulang Tahun</label>
                    <input id="wa_birthday_auto_time" name="wa_birthday_auto_time" type="time"
                           value="{{ old('wa_birthday_auto_time', $settings['wa_birthday_auto_time']) }}">
                </div>
            </div>
            <div style="margin-top: 10px;">
                <button class="btn" type="submit">Simpan Pengaturan</button>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('gateway-settings.test') }}" style="margin-top: 8px;">
        @csrf
        <button class="btn btn-muted" type="submit">Tes Koneksi Hub + Panel</button>
    </form>

    <div class="panel">
        <strong>Status konfigurasi:</strong>
        <div style="margin-top: 8px;">
            @if (!empty($settings['gateway_app_api_key']) && !empty($settings['gateway_session_id']))
                <span class="badge badge-success">Siap kirim WA</span>
            @else
                <span class="badge badge-warn">Belum lengkap</span>
            @endif
            <span class="badge badge-info">Base URL: {{ $settings['gateway_base_url'] ?: '-' }}</span>
            <span class="badge badge-info">Panel URL: {{ $settings['gateway_panel_url'] ?? '-' }}</span>
            <span class="badge badge-info">App ID: {{ $settings['gateway_app_id'] ?? '-' }}</span>
            @php($sessionState = strtolower((string) ($session_status['state'] ?? 'skip')))
            <span class="badge {{ $sessionState === 'connected' ? 'badge-success' : ($sessionState === 'skip' ? 'badge-info' : 'badge-warn') }}">
                Session Status: {{ $session_status['label'] ?? 'N/A' }}
            </span>
        </div>
        @if (!empty($session_status['detail']))
            <div style="margin-top:8px; color: var(--muted); font-size: 13px;">{{ $session_status['detail'] }}</div>
        @endif

        @if (session('gateway_test'))
            @php($t = session('gateway_test'))
            <div style="margin-top: 10px;">
                <span class="badge {{ ($t['hub_health'] ?? '') === 'ok' ? 'badge-success' : 'badge-warn' }}">
                    Hub Health: {{ strtoupper($t['hub_health'] ?? 'n/a') }}
                </span>
                <span class="badge {{ ($t['hub_session'] ?? '') === 'ok' ? 'badge-success' : (($t['hub_session'] ?? '') === 'skip' ? 'badge-info' : 'badge-warn') }}">
                    Hub Session: {{ strtoupper($t['hub_session'] ?? 'n/a') }}
                </span>
                <span class="badge {{ ($t['panel_login'] ?? '') === 'ok' ? 'badge-success' : (($t['panel_login'] ?? '') === 'skip' ? 'badge-info' : 'badge-warn') }}">
                    Panel Login: {{ strtoupper($t['panel_login'] ?? 'n/a') }}
                </span>
            </div>
            @if (!empty($t['detail']) && is_array($t['detail']))
                <ul style="margin-top:10px; color: var(--muted);">
                    @foreach ($t['detail'] as $d)
                        <li>{{ $d }}</li>
                    @endforeach
                </ul>
            @endif
        @endif
    </div>
@endsection
