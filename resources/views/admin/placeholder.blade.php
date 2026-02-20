@extends('layouts.admin')

@section('title', $title)

@section('content')
    <h2 style="margin:0 0 4px;">{{ $title }}</h2>
    <p style="margin:0 0 16px;color:#64748b;">{{ $description }}</p>

    <div style="border:1px dashed #c9d5e2;border-radius:12px;padding:16px;background:#f8fbfd;">
        <div style="font-size:14px;color:#324154;line-height:1.6;">
            Halaman ini siap dipakai untuk modul <b>{{ $title }}</b>.
            Backend API untuk modul ini sudah tersedia dan siap dihubungkan ke tabel/action UI.
        </div>
    </div>
@endsection
