@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <h2 style="margin:0 0 4px;">Dashboard</h2>
    <p style="margin:0 0 16px;color:#64748b;">Ringkasan operasional backend Sinyal Saham Indo.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;">
        <section style="background:#f3f9ff;border:1px solid #cde4ff;border-radius:12px;padding:14px;">
            <div style="font-size:13px;color:#64748b;">Total Klient</div>
            <div style="font-size:28px;font-weight:700;color:#178dff;">{{ $clientsCount }}</div>
        </section>
        <section style="background:#f3f9ff;border:1px solid #cde4ff;border-radius:12px;padding:14px;">
            <div style="font-size:13px;color:#64748b;">Klient Aktif</div>
            <div style="font-size:28px;font-weight:700;color:#178dff;">{{ $activeClientsCount }}</div>
        </section>
        <section style="background:#f3f9ff;border:1px solid #cde4ff;border-radius:12px;padding:14px;">
            <div style="font-size:13px;color:#64748b;">Tier Modal</div>
            <div style="font-size:28px;font-weight:700;color:#178dff;">{{ $tiersCount }}</div>
        </section>
        <section style="background:#f3f9ff;border:1px solid #cde4ff;border-radius:12px;padding:14px;">
            <div style="font-size:13px;color:#64748b;">Sinyal Aktif</div>
            <div style="font-size:28px;font-weight:700;color:#178dff;">{{ $signalsCount }}</div>
        </section>
        <section style="background:#f3f9ff;border:1px solid #cde4ff;border-radius:12px;padding:14px;">
            <div style="font-size:13px;color:#64748b;">Template WA</div>
            <div style="font-size:28px;font-weight:700;color:#178dff;">{{ $templatesCount }}</div>
        </section>
    </div>
@endsection
