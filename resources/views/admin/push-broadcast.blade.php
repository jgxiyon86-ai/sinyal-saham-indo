@extends('layouts.admin')

@section('title', 'Push Broadcast')

@section('content')
    <h2 style="margin:0 0 4px;">Push Broadcast</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Kirim notifikasi push ke client Android berdasarkan tier.</p>

    @if ($errors->any())
        <div class="status" style="background:#fff4f4;border-color:#ffc8c8;color:#b33a3a;">{{ $errors->first() }}</div>
    @endif

    <div class="panel">
        <form method="POST" action="{{ route('push.send') }}">
            @csrf
            <div class="field-grid">
                <div>
                    <label>Tier Target</label>
                    <select name="tier_id">
                        <option value="">Semua Tier</option>
                        @foreach($tiers as $tier)
                            <option value="{{ $tier->id }}" @selected((string) old('tier_id') === (string) $tier->id)>{{ $tier->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div><label>Judul Push</label><input name="title" value="{{ old('title') }}" maxlength="120" required></div>
                <div style="grid-column:1/-1;"><label>Isi Pesan</label><textarea name="body" maxlength="500" required>{{ old('body') }}</textarea></div>
            </div>
            <div style="margin-top:10px;">
                <button class="btn" type="submit" onclick="return confirm('Kirim push sekarang?')">Kirim Push</button>
            </div>
        </form>
    </div>
@endsection

