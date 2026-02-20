@extends('layouts.admin')

@section('title', $signal ? 'Edit Sinyal' : 'Tambah Sinyal')

@section('content')
    <h2 style="margin:0 0 4px;">{{ $signal ? 'Edit Sinyal' : 'Tambah Sinyal' }}</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Form input sinyal saham.</p>

    @if ($errors->any())
        <div class="status" style="background:#fff4f4;border-color:#ffc8c8;color:#b33a3a;">{{ $errors->first() }}</div>
    @endif

    <div class="panel">
        <form method="POST" action="{{ $signal ? url('/signals/'.$signal->id) : url('/signals') }}">
            @csrf
            @if ($signal) @method('PUT') @endif
            <div class="field-grid">
                <div><label>Judul</label><input name="title" value="{{ old('title', $signal->title ?? '') }}" required></div>
                <div><label>Kode Saham</label><input name="stock_code" value="{{ old('stock_code', $signal->stock_code ?? '') }}" required></div>
                <div>
                    <label>Tipe</label>
                    <select name="signal_type" required>
                        @foreach(['buy' => 'Buy', 'sell' => 'Sell', 'hold' => 'Hold'] as $key => $label)
                            <option value="{{ $key }}" @selected(old('signal_type', $signal->signal_type ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Entry</label><input type="number" step="0.01" name="entry_price" value="{{ old('entry_price', $signal->entry_price ?? '') }}"></div>
                <div><label>Take Profit</label><input type="number" step="0.01" name="take_profit" value="{{ old('take_profit', $signal->take_profit ?? '') }}"></div>
                <div><label>Stop Loss</label><input type="number" step="0.01" name="stop_loss" value="{{ old('stop_loss', $signal->stop_loss ?? '') }}"></div>
                <div><label>Tanggal Publikasi</label><input type="datetime-local" name="published_at" value="{{ old('published_at', optional($signal?->published_at)->format('Y-m-d\\TH:i')) }}"></div>
                <div>
                    <label>Tier Target</label>
                    @php($totalTierCount = $tiers->count())
                    @php($signalTierIds = $signal?->tiers->pluck('id')->toArray() ?? [])
                    @php($defaultTarget = 'all')
                    @if(!empty($signalTierIds) && count($signalTierIds) === 1)
                        @php($defaultTarget = (string) $signalTierIds[0])
                    @elseif(!empty($signalTierIds) && count($signalTierIds) !== $totalTierCount)
                        @php($defaultTarget = 'all')
                    @endif
                    @php($selectedTierTarget = old('tier_target', $defaultTarget))
                    <select name="tier_target" required>
                        <option value="all" @selected($selectedTierTarget === 'all')>Semua Tier</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected($selectedTierTarget === (string)$tier->id)>{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Berlaku Sampai (opsional)</label><input type="datetime-local" name="expires_at" value="{{ old('expires_at', optional($signal?->expires_at)->format('Y-m-d\\TH:i')) }}"></div>
                <div style="grid-column:1/-1;"><label>Catatan</label><textarea name="note">{{ old('note', $signal->note ?? '') }}</textarea></div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button class="btn" type="submit">{{ $signal ? 'Update Sinyal' : 'Simpan Sinyal' }}</button>
                <a class="btn btn-muted" href="{{ route('signals.page') }}" style="text-decoration:none;">Kembali ke List</a>
            </div>
        </form>
    </div>
@endsection
