@extends('layouts.admin')

@section('title', 'Manajemen Klient')

@section('content')
    <h2 style="margin:0 0 4px;">Manajemen Klient</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Tampilan list untuk data besar. Form dipisah ke halaman lain.</p>

    <div class="panel">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:10px;">
            <div style="background:#fff;border:1px solid #d5e6fb;border-radius:10px;padding:12px;">
                <div style="font-size:12px;color:#4d6b8f;">Total Klient</div>
                <div style="font-size:20px;font-weight:700;">{{ number_format((int) ($summary['total'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div style="background:#ecfdf3;border:1px solid #9de7bf;border-radius:10px;padding:12px;">
                <div style="font-size:12px;color:#166534;">Klient Aktif</div>
                <div style="font-size:20px;font-weight:700;color:#166534;">{{ number_format((int) ($summary['active'] ?? 0), 0, ',', '.') }}</div>
            </div>
            <div style="background:#fff8e1;border:1px solid #f6dd8f;border-radius:10px;padding:12px;">
                <div style="font-size:12px;color:#8a5b00;">Klient Nonaktif</div>
                <div style="font-size:20px;font-weight:700;color:#8a5b00;">{{ number_format((int) ($summary['inactive'] ?? 0), 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <form method="GET" action="{{ route('clients.page') }}">
            <div class="field-grid">
                <div><label>Cari</label><input name="q" value="{{ request('q') }}" placeholder="IDCUST, nama, email, no HP"></div>
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
                <th>IDCUST</th><th>Nama</th><th>Email</th><th>Tier</th><th>Modal</th><th>Agama</th><th>Nomor HP</th><th>Status</th><th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @forelse($clients as $client)
                <tr>
                    <td>{{ $client->client_code ?: '-' }}</td>
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
                            <form method="POST" action="{{ route('clients.toggle-active', $client) }}" onsubmit="return confirm('{{ $client->is_active ? 'Nonaktifkan klient ini? Klient tidak akan menerima WA/sinyal aplikasi.' : 'Aktifkan kembali klient ini?' }}')">
                                @csrf
                                <input type="hidden" name="is_active" value="{{ $client->is_active ? 0 : 1 }}">
                                <button class="btn {{ $client->is_active ? 'btn-danger' : '' }}" type="submit">
                                    {{ $client->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('clients.send-credentials', $client) }}" onsubmit="return confirm('Kirim password baru ke email klient ini?')">
                                @csrf
                                <button class="btn" type="submit">Kirim Kredensial</button>
                            </form>
                            <form method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Hapus klient ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9">Belum ada data klient.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination" style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;">
        <div style="font-size:13px;color:#4d6b8f;">
            Showing {{ $clients->firstItem() ?? 0 }} to {{ $clients->lastItem() ?? 0 }} of {{ $clients->total() }} results
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            @if ($clients->onFirstPage())
                <span class="btn btn-muted" style="opacity:.6;cursor:not-allowed;">Previous</span>
            @else
                <a class="btn btn-muted" href="{{ $clients->previousPageUrl() }}" style="text-decoration:none;">Previous</a>
            @endif

            <span style="font-size:13px;color:#4d6b8f;">Page {{ $clients->currentPage() }} / {{ $clients->lastPage() }}</span>

            @if ($clients->hasMorePages())
                <a class="btn btn-muted" href="{{ $clients->nextPageUrl() }}" style="text-decoration:none;">Next</a>
            @else
                <span class="btn btn-muted" style="opacity:.6;cursor:not-allowed;">Next</span>
            @endif
        </div>
    </div>
@endsection
