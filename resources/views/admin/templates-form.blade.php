@extends('layouts.admin')

@section('title', $template ? 'Edit Template' : 'Tambah Template')

@section('content')
    <h2 style="margin:0 0 4px;">{{ $template ? 'Edit Template' : 'Tambah Template' }}</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Form template pesan WA.</p>

    @if ($errors->any())
        <div class="status" style="background:#fff4f4;border-color:#ffc8c8;color:#b33a3a;">{{ $errors->first() }}</div>
    @endif

    <div class="panel">
        <form method="POST" action="{{ $template ? route('templates.update', $template) : route('templates.store') }}" enctype="multipart/form-data">
            @csrf
            @if ($template) @method('PUT') @endif
            <div class="field-grid">
                <div><label>Nama Template</label><input name="name" value="{{ old('name', $template->name ?? '') }}" required></div>
                <div>
                    <label>Jenis Event</label>
                    <select id="event_type" name="event_type" required>
                        @foreach(['birthday' => 'Ulang Tahun', 'holiday' => 'Hari Raya', 'general' => 'Umum'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('event_type', $template->event_type ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label id="religion_label">Agama (opsional)</label>
                    <select id="religion" name="religion">
                        <option value="">Semua agama</option>
                        @foreach($religions as $key => $label)
                            <option value="{{ $key }}" @selected(old('religion', $template->religion ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Status</label>
                    <select name="is_active">
                        <option value="1" @selected(old('is_active', $template->is_active ?? true))>Aktif</option>
                        <option value="0" @selected(!old('is_active', $template->is_active ?? true))>Nonaktif</option>
                    </select>
                </div>
                <div style="grid-column:1/-1;">
                    <label>Image URL (opsional)</label>
                    <input name="image_url" type="url" value="{{ old('image_url', $template->image_url ?? '') }}" placeholder="https://domain.com/file.jpg">
                </div>
                <div style="grid-column:1/-1;">
                    <label>Upload Gambar (opsional, akan override URL)</label>
                    <input name="image_file" type="file" accept="image/*">
                </div>
                <div style="grid-column:1/-1;"><label>Isi Pesan</label><textarea name="content" required>{{ old('content', $template->content ?? '') }}</textarea></div>
            </div>
            <div style="font-size:12px;color:#4d6b8f;margin-top:8px;">Placeholder: {name}, {tier}, {religion}, {date}, {birth_date}</div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button class="btn" type="submit">{{ $template ? 'Update Template' : 'Simpan Template' }}</button>
                <a class="btn btn-muted" href="{{ route('templates.page') }}" style="text-decoration:none;">Kembali ke List</a>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const eventType = document.getElementById('event_type');
            const religion = document.getElementById('religion');
            const religionLabel = document.getElementById('religion_label');

            function syncReligionRequirement() {
                const isHoliday = eventType.value === 'holiday';
                religion.required = isHoliday;
                religionLabel.textContent = isHoliday ? 'Agama (wajib untuk Hari Raya)' : 'Agama (opsional)';
            }

            eventType.addEventListener('change', syncReligionRequirement);
            syncReligionRequirement();
        })();
    </script>
@endsection
