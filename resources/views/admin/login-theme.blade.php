@extends('layouts.admin')

@section('title', 'Tema UI')

@section('content')
    <h2 style="margin:0 0 6px;">Tema Halaman Login</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Pilih tampilan login global untuk semua admin.</p>

    <div class="panel">
        <form method="POST" action="{{ route('login-theme.login.update') }}">
            @csrf
            <div class="field-grid">
                <div>
                    <label>Pilih Tema</label>
                    <select name="login_theme" required>
                        @foreach($themes as $key => $label)
                            <option value="{{ $key }}" @selected($activeTheme === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="margin-top:10px;">
                <button class="btn" type="submit">Simpan Tema</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h3 style="margin:0 0 8px;">Preview Tema Aktif</h3>
        <div style="font-size:14px;color:#376089;">Tema aktif sekarang: <b>{{ $themes[$activeTheme] ?? 'Modern Blue' }}</b></div>
    </div>

    <h2 style="margin:14px 0 6px;">Tema Panel Admin</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Pilih tampilan untuk semua halaman setelah login admin.</p>

    <div class="panel">
        <form method="POST" action="{{ route('login-theme.panel.update') }}">
            @csrf
            <div class="field-grid">
                <div>
                    <label>Pilih Tema Panel</label>
                    <select name="panel_theme" required>
                        @foreach($panelThemes as $key => $label)
                            <option value="{{ $key }}" @selected($activePanelTheme === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="margin-top:10px;">
                <button class="btn" type="submit">Simpan Tema Panel</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <h3 style="margin:0 0 8px;">Preview Tema Panel Aktif</h3>
        <div style="font-size:14px;color:#376089;">Tema panel aktif: <b>{{ $panelThemes[$activePanelTheme] ?? 'Modern Blue' }}</b></div>
    </div>
@endsection
