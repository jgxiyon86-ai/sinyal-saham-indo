<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>@yield('title', 'Admin Panel') - Sinyal Saham Indo</title>
    @php($panelTheme = \App\Models\AppSetting::getValue('panel_theme', 'modern'))
    <style>
        :root {
            color-scheme: only light;
            --bg: #eaf4ff;
            --sidebar: #0b4ea2;
            --sidebar-muted: #d8e9ff;
            --card: #ffffff;
            --text: #12253d;
            --muted: #4d6b8f;
            --accent: #178dff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: "Segoe UI", Tahoma, sans-serif;
        }
        .app {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px 1fr;
        }
        .sidebar {
            background: linear-gradient(180deg, #0b6be6 0%, var(--sidebar) 100%);
            color: #fff;
            padding: 22px 16px;
        }
        .brand-wrap { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; }
        .brand-logo { width: 34px; height: 34px; border-radius: 10px; box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25); }
        .brand { font-size: 18px; font-weight: 700; margin: 0; line-height: 1.2; }
        .tagline { font-size: 12px; color: var(--sidebar-muted); margin-bottom: 18px; }
        .menu { display: grid; gap: 8px; }
        .menu a {
            color: var(--sidebar-muted);
            text-decoration: none;
            padding: 10px 12px;
            border-radius: 9px;
            display: block;
            font-size: 14px;
        }
        .menu a.active {
            background: rgba(88, 195, 255, 0.2);
            color: #ffffff;
            border: 1px solid rgba(88, 195, 255, 0.7);
        }
        .content {
            padding: 16px;
            display: grid;
            grid-template-rows: auto 1fr auto;
            gap: 14px;
        }
        .topbar {
            background: var(--card);
            border-radius: 12px;
            padding: 12px 14px;
            box-shadow: 0 10px 28px rgba(23, 100, 196, 0.14);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-name { font-weight: 600; font-size: 14px; }
        .topbar form { margin: 0; }
        .logout-btn {
            border: 0;
            background: #ef4444;
            color: #fff;
            border-radius: 8px;
            padding: 8px 12px;
            cursor: pointer;
            font-size: 13px;
        }
        .main-card {
            background: var(--card);
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 28px rgba(23, 100, 196, 0.12);
        }
        .status {
            margin-bottom: 12px;
            background: #e9f6ff;
            border: 1px solid #b8ddff;
            border-radius: 10px;
            padding: 10px 12px;
            color: #0e5ca8;
            font-size: 14px;
        }
        .panel {
            border: 1px solid #d5e6fb;
            border-radius: 12px;
            padding: 14px;
            background: #f6fbff;
            margin-bottom: 14px;
        }
        .field-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
        }
        .field-grid textarea { grid-column: 1 / -1; }
        label {
            display: block;
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 4px;
        }
        input, select, textarea {
            width: 100%;
            border: 1px solid #b8d3f0;
            border-radius: 9px;
            padding: 9px 10px;
            font-size: 14px;
            background: #fff;
            color: #12253d;
        }
        textarea { min-height: 90px; }
        .btn {
            border: 0;
            background: var(--accent);
            color: #fff;
            border-radius: 9px;
            padding: 9px 13px;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-muted { background: #5f7799; }
        .btn-danger { background: #ef4444; }
        .table-wrap { overflow-x: auto; margin-top: 14px; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            min-width: 760px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #e6edf5;
            text-align: left;
            font-size: 13px;
            vertical-align: top;
        }
        th { background: #edf6ff; color: #376089; }
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
            white-space: nowrap;
        }
        .badge-info { background: #e0f2ff; color: #0e5ca8; border: 1px solid #b8ddff; }
        .badge-warn { background: #fff4d6; color: #8a5b00; border: 1px solid #f2d38b; }
        .badge-success { background: #dcfce7; color: #166534; border: 1px solid #a7f3d0; }
        .badge-muted { background: #e5e7eb; color: #374151; border: 1px solid #d1d5db; }
        .pagination { margin-top: 10px; }
        .footer {
            color: var(--muted);
            font-size: 12px;
            text-align: center;
            padding: 8px 0 4px;
        }

        /* Lux theme */
        body.theme-lux {
            --bg: #f4efe4;
            --sidebar: #202635;
            --sidebar-muted: #d8c8a3;
            --card: #fffdfa;
            --text: #2d2417;
            --muted: #6e5d40;
            --accent: #b88a2a;
        }
        body.theme-lux .sidebar {
            background: linear-gradient(180deg, #1d2330 0%, #161c29 100%);
            border-right: 1px solid #2d3446;
        }
        body.theme-lux .menu a.active {
            background: rgba(184, 138, 42, 0.18);
            border-color: rgba(184, 138, 42, 0.7);
            color: #f7e8c8;
        }
        body.theme-lux .topbar,
        body.theme-lux .main-card {
            box-shadow: 0 14px 32px rgba(47, 35, 15, 0.12);
            border: 1px solid #efe3c9;
        }
        body.theme-lux .panel {
            background: #fff8ec;
            border-color: #ead7b0;
        }
        body.theme-lux th {
            background: #f7ecd8;
            color: #6a4f1a;
        }
        body.theme-lux input,
        body.theme-lux select,
        body.theme-lux textarea {
            border-color: #d8c399;
            color: #2d2417;
            background: #fffdf8;
        }
        body.theme-lux .btn {
            background: linear-gradient(180deg, #c79a36 0%, #a97b1f 100%);
            color: #fff9ef;
        }
        body.theme-lux .btn-muted { background: #6e5d40; }
        body.theme-lux .btn-danger { background: #c2413b; }
        body.theme-lux .status {
            background: #fff6e3;
            border-color: #edd7a8;
            color: #8c6514;
        }

        @media (max-width: 920px) {
            .app { grid-template-columns: 1fr; }
            .sidebar { padding: 12px; }
            .menu {
                grid-auto-flow: column;
                grid-auto-columns: max-content;
                overflow-x: auto;
                padding-bottom: 4px;
            }
            .menu a { white-space: nowrap; }
        }
    </style>
</head>
<body class="{{ $panelTheme === 'lux' ? 'theme-lux' : 'theme-modern' }}">
<div class="app">
    <aside class="sidebar">
        <div class="brand-wrap">
            <img class="brand-logo" src="{{ asset('assets/sinyal-saham-logo.svg') }}" alt="Sinyal Saham Indo">
            <div class="brand">Sinyal Saham Indo</div>
        </div>
        <div class="tagline">Admin Backend Panel</div>
        <nav class="menu">
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
            <a href="{{ route('clients.page') }}" class="{{ request()->routeIs('clients.*') ? 'active' : '' }}">Klient</a>
            <a href="{{ route('tiers.page') }}" class="{{ request()->routeIs('tiers.*') ? 'active' : '' }}">Tier Modal</a>
            <a href="{{ route('signals.page') }}" class="{{ request()->routeIs('signals.*') ? 'active' : '' }}">Sinyal</a>
            <a href="{{ route('templates.page') }}" class="{{ request()->routeIs('templates.*') ? 'active' : '' }}">Template Pesan</a>
            <a href="{{ route('wa-blast.page') }}" class="{{ request()->routeIs('wa-blast.*') ? 'active' : '' }}">WA Blast</a>
            <a href="{{ route('signal-wa-blast.page') }}" class="{{ request()->routeIs('signal-wa-blast.*') ? 'active' : '' }}">WA Blast Sinyal</a>
            <a href="{{ route('push.page') }}" class="{{ request()->routeIs('push.*') ? 'active' : '' }}">Push Broadcast</a>
            <a href="{{ route('login-theme.page') }}" class="{{ request()->routeIs('login-theme.*') ? 'active' : '' }}">Tema UI</a>
            <a href="{{ route('gateway-settings.page') }}" class="{{ request()->routeIs('gateway-settings.*') ? 'active' : '' }}">Pengaturan Gateway</a>
        </nav>
    </aside>

    <div class="content">
        <header class="topbar">
            <div class="admin-name">Login sebagai: {{ auth()->user()->name }}</div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="logout-btn" type="submit">Logout</button>
            </form>
        </header>

        <main class="main-card">
            @if (session('status'))
                <div class="status">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>

        <footer class="footer">Copyright &copy; {{ date('Y') }} Alima Creation</footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
