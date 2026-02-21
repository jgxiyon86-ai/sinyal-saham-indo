@extends('layouts.admin')

@section('title', 'Tema Login')

@section('content')
    <h2 style="margin:0 0 6px;">Tema Halaman Login</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Pilih tampilan login global untuk semua admin.</p>

    <div class="panel">
        <form method="POST" action="{{ route('login-theme.update') }}">
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
@endsection

