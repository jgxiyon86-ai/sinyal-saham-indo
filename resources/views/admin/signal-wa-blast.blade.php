@extends('layouts.admin')

@section('title', 'WA Blast Sinyal')

@section('content')
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
        <form method="POST" action="{{ route('signal-wa-blast.preview') }}">
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
                <div style="grid-column:1/-1;"><label>Image URL (opsional)</label><input type="url" name="image_url" value="{{ old('image_url', $settings['image_url']) }}" placeholder="https://domain.com/gambar.jpg"></div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead><tr><th style="width:40px;">Pilih</th><th>Judul</th><th>Kode</th><th>Tipe</th><th>Tier</th><th>Publikasi</th></tr></thead>
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
                        </tr>
                    @empty
                        <tr><td colspan="6">Belum ada sinyal aktif untuk diblast.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div style="margin-top:10px;">
                <button class="btn" type="submit">Preview WA Blast Sinyal</button>
            </div>
        </form>
    </div>

    @if($preview)
        <div class="panel">
            <h3 style="margin:0 0 6px;">Preview Target Blast</h3>
            <div style="font-size:13px;color:#4d6b8f;margin-bottom:10px;">Total target sesuai filter: <b>{{ $preview->count() }}</b></div>

            <form method="POST" action="{{ route('signal-wa-blast.send') }}" style="margin-bottom:10px;">
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
                <button class="btn" type="submit" onclick="return confirm('Kirim WA Blast Sinyal sekarang?')">Kirim WA Blast Sinyal</button>
            </form>

            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nama</th><th>Nomor HP</th><th>Tier</th><th>Jumlah Sinyal</th><th>Pesan</th></tr></thead>
                    <tbody>
                    @foreach($preview as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td>{{ $row['whatsapp_number'] }}</td>
                            <td>{{ $row['tier'] }}</td>
                            <td>{{ $row['signals_count'] }}</td>
                            <td>{{ $row['message'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

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

