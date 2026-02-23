@extends('layouts.admin')

@section('title', 'Pengaturan Gateway')

@section('content')
    <h1 style="margin-top: 0;">Pengaturan Gateway WA</h1>
    <p style="margin-top: 0; color: var(--muted);">Isi di sini agar aplikasi langsung pakai koneksi ALIMA Gateway tanpa edit file <code>.env</code>.</p>

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

    <div class="panel">
        <strong>Status konfigurasi:</strong>
        <div style="margin-top: 8px;">
            @if (!empty($settings['gateway_app_api_key']) && !empty($settings['gateway_session_id']))
                <span class="badge badge-success">Siap kirim WA</span>
            @else
                <span class="badge badge-warn">Belum lengkap</span>
            @endif
            <span class="badge badge-info">Base URL: {{ $settings['gateway_base_url'] ?: '-' }}</span>
        </div>
    </div>
@endsection

