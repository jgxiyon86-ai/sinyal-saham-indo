@extends('layouts.admin')

@section('title', 'Sinyal Saham')

@section('content')
    <h2 style="margin:0 0 4px;">Sinyal Saham</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">List sinyal, form dipisah ke halaman tambah/edit.</p>

    <div class="panel">
        <form method="GET" action="{{ route('signals.page') }}">
            <div class="field-grid">
                <div><label>Cari</label><input name="q" value="{{ request('q') }}" placeholder="Judul atau kode saham"></div>
                <div>
                    <label>Tier</label>
                    <select name="tier_id">
                        <option value="">Semua tier</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected((string)request('tier_id') === (string)$tier->id)>{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button class="btn" type="submit">Filter</button>
                <a class="btn btn-muted" href="{{ route('signals.page') }}" style="text-decoration:none;">Reset</a>
                <a class="btn" href="{{ route('signals.create') }}" style="text-decoration:none;">Tambah Sinyal</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Judul</th><th>Kode</th><th>Tipe</th><th>Tier</th><th>Publikasi</th><th>Status Push</th><th>Berlaku Sampai</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            @forelse($signals as $signal)
                <tr>
                    <td>{{ $signal->title }}</td>
                    <td>{{ $signal->stock_code }}</td>
                    <td>{{ strtoupper($signal->signal_type) }}</td>
                    <td>{{ $signal->tiers->pluck('name')->implode(', ') }}</td>
                    <td>{{ optional($signal->published_at)->format('Y-m-d H:i') }}</td>
                    <td>
                        @if($signal->expires_at && $signal->expires_at->isPast())
                            <span class="badge badge-muted">Expired</span>
                        @elseif($signal->push_sent_at)
                            <span class="badge badge-success">Sudah Dipush</span>
                        @elseif($signal->published_at && $signal->published_at->isFuture())
                            <span class="badge badge-info">Terjadwal</span>
                        @else
                            <span class="badge badge-warn">Menunggu Push</span>
                        @endif
                    </td>
                    <td>{{ optional($signal->expires_at)->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-muted" href="{{ route('signals.edit', $signal) }}" style="text-decoration:none;">Edit</a>
                            <form method="POST" action="{{ route('signals.destroy', $signal) }}" onsubmit="return confirm('Hapus sinyal ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">Belum ada sinyal.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $signals->links() }}</div>
@endsection
