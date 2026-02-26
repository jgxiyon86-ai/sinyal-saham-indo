@extends('layouts.admin')

@section('title', 'WA Blast')

@section('content')
    <h2 style="margin:0 0 4px;">WA Blast Preview</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Preview target, kirim massal, dan manual send.</p>

    @if ($errors->any())
        <div class="status" style="background:#fff4f4;border-color:#ffc8c8;color:#b33a3a;">{{ $errors->first() }}</div>
    @endif

    <div class="panel">
        <h3 style="margin:0 0 10px;">Manual Send</h3>
        <form method="POST" action="{{ route('wa-blast.manual-send') }}" enctype="multipart/form-data">
            @csrf
            <div class="field-grid">
                <div><label>Nomor HP Tujuan</label><input name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="628xxxx" required></div>
                <div>
                    <label>Image URL (opsional)</label>
                    <input id="image-url-manual" name="image_url" type="url" value="{{ old('image_url') }}" placeholder="{{ rtrim(config('app.url'), '/') }}/storage/wa-manual-images/file.jpg">
                    <div style="font-size:12px;color:#4d6b8f;margin-top:4px;">Bisa Ctrl+V screenshot langsung di kolom ini. Rekomendasi: pakai upload agar URL otomatis valid.</div>
                </div>
                <div style="grid-column:1/-1;"><label>Upload Gambar (opsional, akan override URL)</label><input name="image_file" type="file" accept="image/*"></div>
                <div style="grid-column:1/-1;"><label>Pesan (opsional jika ada gambar)</label><textarea name="message">{{ old('message') }}</textarea></div>
            </div>
            <div style="margin-top:10px;"><button class="btn" type="submit" onclick="return confirm('Kirim manual sekarang?')">Kirim Manual</button></div>
        </form>
    </div>

    <div class="panel">
        <h3 style="margin:0 0 10px;">Filter Blast</h3>
        <form method="POST" action="{{ route('wa-blast.preview') }}">
            @csrf
            <div class="field-grid">
                <div>
                    <label>Template</label>
                    <select name="message_template_id" required>
                        <option value="">Pilih template</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" @selected(old('message_template_id') == $template->id)>
                                {{ $template->name }} ({{ $template->event_type }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Tier (opsional)</label>
                    <select name="tier_id">
                        <option value="">Semua tier</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected(old('tier_id') == $tier->id)>{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Agama Filter (opsional)</label>
                    <select name="religion">
                        <option value="">Semua agama</option>
                        @foreach($religions as $key => $label)
                            <option value="{{ $key }}" @selected(old('religion') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Tanggal (untuk birthday)</label><input type="date" name="date" value="{{ old('date', date('Y-m-d')) }}"></div>
            </div>
            <div style="margin-top:10px;"><button class="btn" type="submit">Preview Blast</button></div>
        </form>
    </div>

    @if($preview)
        <div class="panel">
            <h3 style="margin:0 0 6px;">Hasil Preview</h3>
            <div style="font-size:13px;color:#4d6b8f;margin-bottom:10px;">
                Template: <b>{{ $preview['template']->name }}</b>,
                Tanggal: <b>{{ $preview['date'] }}</b>,
                Total Target: <b>{{ $preview['count'] }}</b>
            </div>

            <form method="POST" action="{{ route('wa-blast.send') }}" style="margin-bottom:10px;">
                @csrf
                <input type="hidden" name="message_template_id" value="{{ $preview['template']->id }}">
                <input type="hidden" name="tier_id" value="{{ $preview['filters']['tier_id'] }}">
                <input type="hidden" name="religion" value="{{ $preview['filters']['religion'] }}">
                <input type="hidden" name="date" value="{{ $preview['date'] }}">
                <button class="btn" type="submit" onclick="return confirm('Kirim WA ke semua target preview ini?')">Kirim Sekarang via ALIMA Gateway</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nama</th><th>Nomor HP</th><th>Tier</th><th>Agama</th><th>Gambar</th><th>Pesan</th></tr></thead>
                    <tbody>
                    @forelse($preview['messages'] as $item)
                        <tr>
                            <td>{{ $item['name'] }}</td>
                            <td>{{ $item['whatsapp_number'] }}</td>
                            <td>{{ $item['tier'] }}</td>
                            <td>{{ config('religions.options.'.$item['religion']) ?? '-' }}</td>
                            <td>{{ !empty($item['image_url']) ? 'Ya' : '-' }}</td>
                            <td>{{ $item['message'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Tidak ada target sesuai filter.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="panel">
        <h3 style="margin:0 0 6px;">Riwayat Preview Terbaru</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th>Template</th><th>Tipe</th><th>Target</th><th>Nama & Nomor HP</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    @php($items = collect(json_decode($log->rendered_messages ?? '[]', true))->take(3))
                    <tr>
                        <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->template->name ?? '-' }}</td>
                        <td>{{ strtoupper($log->blast_type) }}</td>
                        <td>{{ $log->recipients_count }}</td>
                        <td>
                            @if($items->isEmpty())
                                -
                            @else
                                @foreach($items as $item)
                                    <div>{{ ($item['name'] ?? '-') }} - {{ ($item['whatsapp_number'] ?? '-') }}</div>
                                @endforeach
                            @endif
                        </td>
                        <td>{{ $log->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">Belum ada log.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $logs->links() }}</div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const input = document.getElementById('image-url-manual');
    if (!input) return;

    async function uploadClipboardImage(file) {
        const formData = new FormData();
        formData.append('clipboard_image', file, 'screenshot.png');

        const resp = await fetch('{{ route('wa-blast.upload-image') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!resp.ok) {
            throw new Error('Upload gambar gagal');
        }

        const json = await resp.json();
        if (!json.url) {
            throw new Error('URL gambar tidak ditemukan');
        }

        input.value = json.url;
    }

    input.addEventListener('paste', async function (e) {
        const items = e.clipboardData?.items || [];
        for (const item of items) {
            if (item.type && item.type.startsWith('image/')) {
                e.preventDefault();
                const file = item.getAsFile();
                if (!file) return;

                const oldPlaceholder = input.placeholder;
                input.placeholder = 'Uploading screenshot...';
                try {
                    await uploadClipboardImage(file);
                } catch (err) {
                    alert(err.message || 'Gagal upload screenshot');
                } finally {
                    input.placeholder = oldPlaceholder;
                }
                return;
            }
        }
    });
})();
</script>
@endpush
