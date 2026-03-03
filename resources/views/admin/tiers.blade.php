@extends('layouts.admin')

@section('title', 'Tier Modal')

@section('content')
    <h2 style="margin:0 0 4px;">Tier Modal</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">List tier terpisah dari form.</p>

    <div style="margin-bottom:10px; display: flex; gap: 8px;">
        <a class="btn" href="{{ route('tiers.create') }}" style="text-decoration:none;">Tambah Tier</a>
        <form action="{{ route('tiers.sync-all') }}" method="POST" onsubmit="return confirm('Proses ini akan mengupdate tier SEMUA klient berdasarkan modal mereka saat ini. Lanjutkan?')">
            @csrf
            <button class="btn btn-muted" type="submit">Sinkronkan Semua Tier Klient</button>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Nama</th><th>Min Modal</th><th>Max Modal</th><th>Batas WA Blast</th><th>Jumlah Klient</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            @forelse($tiers as $tier)
                <tr>
                    <td>{{ $tier->name }}</td>
                    <td>{{ number_format((float)$tier->min_capital, 0, ',', '.') }}</td>
                    <td>{{ $tier->max_capital ? number_format((float)$tier->max_capital, 0, ',', '.') : 'Tanpa Batas' }}</td>
                    <td>{{ (int) $tier->wa_blast_limit }} klient</td>
                    <td>{{ $tier->clients_count }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-muted" href="{{ route('tiers.edit', $tier) }}" style="text-decoration:none;">Edit</a>
                            <form method="POST" action="{{ route('tiers.destroy', $tier) }}" onsubmit="return confirm('Hapus tier ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">Belum ada tier.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
