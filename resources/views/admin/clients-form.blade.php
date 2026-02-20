@extends('layouts.admin')

@section('title', $client ? 'Edit Klient' : 'Tambah Klient')

@section('content')
    <h2 style="margin:0 0 4px;">{{ $client ? 'Edit Klient' : 'Tambah Klient' }}</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Form data klient.</p>

    @if ($errors->any())
        <div class="status" style="background:#fff4f4;border-color:#ffc8c8;color:#b33a3a;">{{ $errors->first() }}</div>
    @endif

    <div class="panel">
        <form method="POST" action="{{ $client ? route('clients.update', $client) : route('clients.store') }}">
            @csrf
            @if ($client) @method('PUT') @endif
            <div class="field-grid">
                <div><label>Nama</label><input name="name" value="{{ old('name', $client->name ?? '') }}" required></div>
                <div><label>Email</label><input type="email" name="email" value="{{ old('email', $client->email ?? '') }}" required></div>
                <div><label>Password {{ $client ? '(opsional)' : '' }}</label><input type="password" name="password" {{ $client ? '' : 'required' }}></div>
                <div><label>Nomor HP</label><input name="whatsapp_number" value="{{ old('whatsapp_number', $client->whatsapp_number ?? '') }}"></div>
                <div><label>Tanggal Lahir</label><input type="date" name="birth_date" value="{{ old('birth_date', optional($client?->birth_date)->format('Y-m-d')) }}"></div>
                <div>
                    <label>Agama</label>
                    <select name="religion">
                        <option value="">Pilih agama</option>
                        @foreach($religions as $key => $label)
                            <option value="{{ $key }}" @selected(old('religion', $client->religion ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Modal</label><input type="number" step="0.01" name="capital_amount" value="{{ old('capital_amount', $client->capital_amount ?? '0') }}" required></div>
                <div>
                    <label>Status Aktif</label>
                    <select name="is_active">
                        <option value="1" @selected(old('is_active', $client->is_active ?? true))>Aktif</option>
                        <option value="0" @selected(!old('is_active', $client->is_active ?? true))>Nonaktif</option>
                    </select>
                </div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:26px;">
                    <input id="send_email_credentials" type="checkbox" name="send_email_credentials" value="1" style="width:auto;" @checked(old('send_email_credentials', $client ? false : true))>
                    <label for="send_email_credentials" style="margin:0;">Kirim email username & password</label>
                </div>
                <div style="grid-column:1/-1;"><label>Alamat</label><textarea name="address">{{ old('address', $client->address ?? '') }}</textarea></div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button class="btn" type="submit">{{ $client ? 'Update Klient' : 'Simpan Klient' }}</button>
                <a class="btn btn-muted" href="{{ route('clients.page') }}" style="text-decoration:none;">Kembali ke List</a>
            </div>
        </form>
    </div>
@endsection
