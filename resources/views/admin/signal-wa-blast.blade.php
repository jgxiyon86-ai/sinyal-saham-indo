@extends('layouts.admin')

@section('title', 'WA Blast Sinyal')

@section('content')
    <style>
        .wa-loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 23, 41, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .wa-loading-box {
            background: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            min-width: 260px;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
            font-size: 14px;
            color: #123;
        }
        .wa-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #1b74e4;
            display: inline-block;
            margin-right: 4px;
            animation: waPulse 1s infinite ease-in-out;
        }
        .wa-dot:nth-child(2) { animation-delay: 0.15s; }
        .wa-dot:nth-child(3) { animation-delay: 0.3s; }
        @keyframes waPulse {
            0%, 100% { opacity: 0.25; transform: translateY(0); }
            50% { opacity: 1; transform: translateY(-2px); }
        }
    </style>

    <h2 style="margin:0 0 4px;">WA Blast Sinyal</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Input beberapa sinyal dulu, lalu kirim blast WA ke klient sesuai tier.</p>

    @if ($errors->any())
        <div class="status" style="background:#fff4f4;border-color:#ffc8c8;color:#b33a3a;">{{ $errors->first() }}</div>
    @endif

    <div class="panel">
        <h3 style="margin:0 0 8px;">Pengaturan Aman Nomor</h3>
        <div style="font-size:13px;color:#4d6b8f;">
            Gunakan delay 10-20 detik, maksimal 30-60 nomor per blast, hindari isi spam/duplikat berulang.
        </div>
    </div>

    <div class="panel">
        <h3 style="margin:0 0 10px;">Pilih Sinyal + Filter Target</h3>
        <form id="preview-form" method="POST" action="{{ route('signal-wa-blast.preview') }}">
            @csrf
            <div class="field-grid">
                <div>
                    <label>Filter Tier Klient (opsional)</label>
                    <select name="tier_id">
                        <option value="">Semua tier</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected((string)$selectedTierId === (string)$tier->id)>{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Delay antar kirim (detik)</label><input type="number" min="3" max="120" name="delay_seconds" value="{{ old('delay_seconds', $settings['delay_seconds']) }}" required></div>
                <div><label>Maksimal target per blast</label><input type="number" min="1" max="300" name="max_recipients" value="{{ old('max_recipients', $settings['max_recipients']) }}" required></div>
                <div style="grid-column:1/-1;"><label>Opening</label><input name="opening_text" value="{{ old('opening_text', $settings['opening_text']) }}"></div>
                <div style="grid-column:1/-1;"><label>Closing</label><input name="closing_text" value="{{ old('closing_text', $settings['closing_text']) }}"></div>
                <div style="grid-column:1/-1;">
                    <label>Image URL Fallback (opsional)</label>
                    <input id="image-url-signal-blast" type="url" name="image_url" value="{{ old('image_url', $settings['image_url']) }}" placeholder="https://domain.com/gambar.jpg">
                    <div style="font-size:12px;color:#4d6b8f;margin-top:4px;">Default pakai gambar per-sinyal. Kolom ini hanya fallback. Bisa Ctrl+V screenshot langsung.</div>
                </div>
                <div style="grid-column:1/-1; display:flex; align-items:center; gap:8px;">
                    <input type="checkbox" name="group_messages" value="1" id="group_messages" @checked(old('group_messages', $settings['group_messages']))>
                    <label for="group_messages" style="margin:0; font-weight:bold; cursor:pointer;">Gabung beberapa sinyal jadi 1 pesan WA (Rekomendasi)</label>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead><tr><th style="width:40px;">Pilih</th><th>Judul</th><th>Kode</th><th>Tipe</th><th>Tier</th><th>Publikasi</th><th>Expired</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse($signals as $signal)
                        <tr>
                            <td>
                                <input type="checkbox" name="signal_ids[]" value="{{ $signal->id }}"
                                       @checked(in_array($signal->id, old('signal_ids', $selectedSignalIds), true))>
                            </td>
                            <td>{{ $signal->title }}</td>
                            <td>{{ strtoupper($signal->stock_code) }}</td>
                            <td>{{ strtoupper($signal->signal_type) }}</td>
                            <td>{{ $signal->tiers->pluck('name')->implode(', ') }}</td>
                            <td>{{ $signal->published_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $signal->expires_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('signals.destroy', $signal) }}" onsubmit="return confirm('Hapus sinyal ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8">Belum ada sinyal aktif untuk diblast.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:10px; display:flex; gap:10px;">
                <button id="btn-preview-submit" class="btn" type="submit">Preview WA Blast Sinyal</button>
                <button class="btn btn-muted" type="submit" formaction="{{ route('signal-wa-blast.settings.save') }}">Simpan Template (Opening/Closing)</button>
            </div>
        </form>

    </div>

    @if($preview)
        <div class="panel">
            <h3 style="margin:0 0 6px;">Preview Target Blast</h3>
            <div style="font-size:13px;color:#4d6b8f;margin-bottom:10px;">Total target sesuai filter: <b>{{ $preview->count() }}</b></div>

            <form id="send-form" method="POST" action="{{ route('signal-wa-blast.send') }}" style="margin-bottom:10px;">
                @csrf
                @foreach($selectedSignalIds as $signalId)
                    <input type="hidden" name="signal_ids[]" value="{{ $signalId }}">
                @endforeach
                <input type="hidden" name="tier_id" value="{{ $selectedTierId }}">
                <input type="hidden" name="delay_seconds" value="{{ $settings['delay_seconds'] }}">
                <input type="hidden" name="max_recipients" value="{{ $settings['max_recipients'] }}">
                <input type="hidden" name="opening_text" value="{{ $settings['opening_text'] }}">
                <input type="hidden" name="closing_text" value="{{ $settings['closing_text'] }}">
                <input type="hidden" name="image_url" value="{{ $settings['image_url'] }}">
                <input type="hidden" name="group_messages" value="{{ $settings['group_messages'] ? '1' : '0' }}">
                <button id="btn-send-submit" class="btn" type="submit" onclick="return confirm('Masukkan WA Blast Sinyal ke antrian sekarang?')">Queue WA Blast Sinyal</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nama</th><th>Nomor HP</th><th>Tier</th><th>Jumlah Sinyal</th><th>Gambar Per Sinyal</th><th>Pesan</th></tr></thead>
                    <tbody>
                    @foreach($preview as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['whatsapp_number'] }}</td>
                            <td>{{ $row['tier'] }}</td>
                            <td>{{ $row['signals_count'] }}</td>
                            <td>
                                @php($withImage = collect($row['signal_items'])->filter(fn($x) => !empty($x['image_url']))->count())
                                {{ $withImage }}/{{ $row['signals_count'] }}
                            </td>
                            <td>{{ $row['message'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="panel">
        <h3 style="margin:0 0 6px;">Status Queue Blast</h3>
        <div class="table-wrap" style="margin-bottom:10px;">
            <table>
                <thead><tr><th>Batch</th><th>Waktu</th><th>Status</th><th>Total</th><th>Pending</th><th>Sent</th><th>Failed</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($queueBatches as $batch)
                    <tr>
                        <td>#{{ $batch->id }}</td>
                        <td>{{ $batch->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ strtoupper($batch->status) }}</td>
                        <td>{{ $batch->total_targets }}</td>
                        <td>{{ $batch->pending_count }}</td>
                        <td>{{ $batch->sent_count }}</td>
                        <td>{{ $batch->failed_count }}</td>
                        <td style="display:flex; gap:6px; flex-wrap:wrap;">
                            <a class="btn btn-muted" href="{{ route('signal-wa-blast.page', ['batch_id' => $batch->id]) }}" style="text-decoration:none;">Lihat Target</a>
                            @if((int) $batch->failed_count > 0)
                                <form method="POST" action="{{ route('signal-wa-blast.resend-failed', $batch) }}" onsubmit="return confirm('Resend semua target FAILED di batch ini?')">
                                    @csrf
                                    <button type="submit" class="btn">Resend Failed</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8">Belum ada queue blast.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <h3 style="margin:0 0 6px;">Status per Target {{ $activeBatch ? "(Batch #{$activeBatch->id})" : '' }}</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Nama</th><th>Nomor</th><th>Sinyal</th><th>Status</th><th>Percobaan</th><th>Error/Response</th><th>Waktu</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse($queueTargets as $target)
                    <tr>
                        <td>{{ $target->id }}</td>
                        <td>{{ $target->client_name }}</td>
                        <td>{{ $target->whatsapp_number }}</td>
                        <td>{{ $target->signal_title ?? '-' }}</td>
                        <td>{{ strtoupper($target->status) }}</td>
                        <td>{{ $target->attempts }}</td>
                        <td style="max-width:380px;white-space:normal;word-break:break-word;">
                            @if($target->status === 'failed')
                                {{ $target->last_error }}
                            @elseif($target->status === 'sent')
                                {{ is_array($target->response_payload) ? 'success' : '-' }}
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $target->sent_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                        <td>
                            @if((string) $target->status === 'failed')
                                <form method="POST" action="{{ route('signal-wa-blast.resend-target', $target) }}" onsubmit="return confirm('Resend target ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-muted">Resend</button>
                                </form>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">Belum ada data target.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $queueTargets->links() }}</div>
    </div>

    <div class="panel">
        <h3 style="margin:0 0 6px;">Riwayat WA Blast Sinyal</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Waktu</th><th>Admin</th><th>Target</th><th>Status</th><th>Setting</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td>{{ $log->admin->name ?? '-' }}</td>
                        <td>{{ $log->recipients_count }}</td>
                        <td>{{ $log->status }}</td>
                        <td>
                            Delay: {{ data_get($log->filters, 'delay_seconds', '-') }} dtk,
                            Max: {{ data_get($log->filters, 'max_recipients', '-') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5">Belum ada riwayat.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $logs->links() }}</div>
    </div>
@endsection

@push('scripts')
<div id="wa-loading-overlay" class="wa-loading-overlay">
    <div class="wa-loading-box">
        <div style="display:flex; align-items:center; margin-bottom:8px;">
            <span class="wa-dot"></span><span class="wa-dot"></span><span class="wa-dot"></span>
        </div>
        <div id="wa-loading-text">Memproses blast, mohon tunggu...</div>
    </div>
</div>
<script>
(function () {
    const input = document.getElementById('image-url-signal-blast');
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

(function () {
    const overlay = document.getElementById('wa-loading-overlay');
    const loadingText = document.getElementById('wa-loading-text');
    const previewForm = document.getElementById('preview-form');
    const sendForm = document.getElementById('send-form');
    const previewButton = document.getElementById('btn-preview-submit');
    const sendButton = document.getElementById('btn-send-submit');

    function showLoading(text) {
        if (!overlay) return;
        if (loadingText && text) loadingText.textContent = text;
        overlay.style.display = 'flex';
    }

    if (previewForm) {
        previewForm.addEventListener('submit', function () {
            if (previewButton) {
                previewButton.disabled = true;
                previewButton.textContent = 'Memproses...';
            }
            showLoading('Menyusun preview target blast...');
        });
    }

    if (sendForm) {
        sendForm.addEventListener('submit', function () {
            if (sendButton) {
                sendButton.disabled = true;
                sendButton.textContent = 'Mengirim...';
            }
            showLoading('Menyimpan antrian WA Blast Sinyal...');
        });
    }
})();

(function () {
    const activeBatch = @json($activeBatch ? ['id' => $activeBatch->id, 'pending' => $activeBatch->pending_count, 'status' => $activeBatch->status] : null);
    if (!activeBatch) return;
    if ((activeBatch.pending || 0) > 0 || activeBatch.status === 'queued' || activeBatch.status === 'processing') {
        setTimeout(() => {
            window.location.reload();
        }, 10000);
    }
})();
</script>
@endpush
