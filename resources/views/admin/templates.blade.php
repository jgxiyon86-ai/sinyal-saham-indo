@extends('layouts.admin')

@section('title', 'Template Pesan')

@section('content')
    <h2 style="margin:0 0 4px;">Template Pesan</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">List template. Form tambah/edit di halaman terpisah.</p>

    <div class="panel">
        <form method="GET" action="{{ route('templates.page') }}">
            <div class="field-grid">
                <div>
                    <label>Jenis Event</label>
                    <select name="event_type">
                        <option value="">Semua</option>
                        <option value="birthday" @selected(request('event_type') === 'birthday')>Ulang Tahun</option>
                        <option value="holiday" @selected(request('event_type') === 'holiday')>Hari Raya</option>
                        <option value="general" @selected(request('event_type') === 'general')>Umum</option>
                    </select>
                </div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button class="btn" type="submit">Filter</button>
                <a class="btn btn-muted" href="{{ route('templates.page') }}" style="text-decoration:none;">Reset</a>
                <a class="btn" href="{{ route('templates.create') }}" style="text-decoration:none;">Tambah Template</a>
            </div>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
            <tr><th>Nama</th><th>Event</th><th>Agama</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
            @forelse($templates as $template)
                <tr>
                    <td>{{ $template->name }}</td>
                    <td>{{ strtoupper($template->event_type) }}</td>
                    <td>{{ config('religions.options.'.$template->religion) ?? '-' }}</td>
                    <td>{{ $template->is_active ? 'Aktif' : 'Nonaktif' }}</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-muted" href="{{ route('templates.edit', $template) }}" style="text-decoration:none;">Edit</a>
                            <form method="POST" action="{{ route('templates.destroy', $template) }}" onsubmit="return confirm('Hapus template ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Belum ada template.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $templates->links() }}</div>
@endsection
