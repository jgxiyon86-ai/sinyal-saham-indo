@extends('layouts.admin')

@section('title', 'Manajemen Klient')

@section('content')
    <h2 style="margin:0 0 4px;">Manajemen Klient</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Tampilan list untuk data besar. Form dipisah ke halaman lain.</p>

    <div class="panel">
        <form method="GET" action="{{ route('clients.page') }}">
            <div class="field-grid">
                <div><label>Cari</label><input name="q" value="{{ request('q') }}" placeholder="Nama, email, no HP"></div>
                <div>
                    <label>Tier</label>
                    <select name="tier_id">
                        <option value="">Semua tier</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected((string)request('tier_id') === (string)$tier->id)>{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Agama</label>
                    <select name="religion">
                        <option value="">Semua agama</option>
                        @foreach($religions as $key => $label)
                            <option value="{{ $key }}" @selected(request('religion') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Status</label>
                    <select name="is_active">
                        <option value="">Semua</option>
                        <option value="1" @selected(request('is_active') === '1')>Aktif</option>
                        <option value="0" @selected(request('is_active') === '0')>Nonaktif</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button class="btn" type="submit">Filter</button>
                <a class="btn btn-muted" href="{{ route('clients.page') }}" style="text-decoration:none;">Reset</a>
                <a class="btn" href="{{ route('clients.create') }}" style="text-decoration:none;">Tambah Klient</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Nama</th><th>Email</th><th>Tier</th><th>Modal</th><th>Agama</th><th>Nomor HP</th><th>Status</th><th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse($clients as $client)
                <tr>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->email }}</td>
                    <td>{{ $client->tier->name ?? '-' }}</td>
                    <td>{{ number_format((float)$client->capital_amount, 0, ',', '.') }}</td>
                    <td>{{ $religions[$client->religion] ?? '-' }}</td>
                    <td>{{ $client->whatsapp_number ?? '-' }}</td>
                    <td>{{ $client->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-muted" href="{{ route('clients.edit', $client) }}" style="text-decoration:none;">Edit</a>
                            <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Hapus klient ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada data klient.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $clients->links() }}</div>
@endsection
