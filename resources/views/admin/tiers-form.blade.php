@extends('layouts.admin')

@section('title', $tier ? 'Edit Tier' : 'Tambah Tier')

@section('content')
    <h2 style="margin:0 0 4px;">{{ $tier ? 'Edit Tier' : 'Tambah Tier' }}</h2>
    <p style="margin:0 0 14px;color:#4d6b8f;">Form tier modal.</p>

    @if ($errors->any())
        <div class="status" style="background:#fff4f4;border-color:#ffc8c8;color:#b33a3a;">{{ $errors->first() }}</div>
    @endif

    <div class="panel">
        <form method="POST" action="{{ $tier ? route('tiers.update', $tier) : route('tiers.store') }}">
            @csrf
            @if ($tier) @method('PUT') @endif
            <div class="field-grid">
                <div><label>Nama Tier</label><input name="name" value="{{ old('name', $tier->name ?? '') }}" required></div>
                <div><label>Min Modal</label><input type="number" step="0.01" name="min_capital" value="{{ old('min_capital', $tier->min_capital ?? '') }}" required></div>
                <div><label>Max Modal (opsional)</label><input type="number" step="0.01" name="max_capital" value="{{ old('max_capital', $tier->max_capital ?? '') }}"></div>
                <div><label>Batas WA Blast per Tier</label><input type="number" min="1" max="5000" name="wa_blast_limit" value="{{ old('wa_blast_limit', $tier->wa_blast_limit ?? 60) }}" required></div>
                <div style="grid-column:1/-1;"><label>Deskripsi</label><textarea name="description">{{ old('description', $tier->description ?? '') }}</textarea></div>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button class="btn" type="submit">{{ $tier ? 'Update Tier' : 'Simpan Tier' }}</button>
                <a class="btn btn-muted" href="{{ route('tiers.page') }}" style="text-decoration:none;">Kembali ke List</a>
            </div>
        </form>
    </div>
@endsection
