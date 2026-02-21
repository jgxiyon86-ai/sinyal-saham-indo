<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <title>Login Admin - Sinyal Saham Indo</title>
    <style>
        :root {
            color-scheme: only light;
            --bg: #0d3b78;
            --panel: #0a4f9e;
            --panel2: #0d63be;
            --accent: #59c2ff;
            --text: #eef2f7;
            --muted: #d8e6ff;
            --danger: #ff8080;
            --input-bg: #0b3f7d;
            --input-border: #3b5f6a;
            --btn-text: #042443;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: radial-gradient(circle at top right, #4aa8ff 0, var(--bg) 55%);
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 20px;
            transition: background .25s ease;
        }
        .page {
            width: 100%;
            max-width: 430px;
        }
        .template-row {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 10px;
        }
        .template-row select {
            width: auto;
            min-width: 175px;
            border: 1px solid rgba(200, 232, 255, .65);
            background: rgba(8, 47, 99, .65);
            color: #eef6ff;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            backdrop-filter: blur(4px);
        }
        .card {
            width: 100%;
            background: linear-gradient(145deg, var(--panel), var(--panel2));
            border: 1px solid #64b8ff;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
            transition: all .25s ease;
        }
        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.35);
        }
        .brand-title {
            font-size: 16px;
            margin: 0;
            font-weight: 700;
            color: #dff1ff;
        }
        h1 { margin: 0 0 6px; font-size: 24px; }
        p { margin: 0 0 20px; color: var(--muted); }
        label { display: block; margin: 14px 0 6px; font-size: 14px; color: var(--muted); }
        input {
            width: 100%;
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            color: var(--text);
            border-radius: 10px;
            padding: 12px 13px;
            font-size: 15px;
        }
        input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(89, 194, 255, 0.2);
        }
        button {
            margin-top: 18px;
            width: 100%;
            border: 0;
            border-radius: 10px;
            background: var(--accent);
            color: var(--btn-text);
            font-weight: 700;
            padding: 12px;
            cursor: pointer;
        }
        .error {
            margin-top: 12px;
            color: var(--danger);
            font-size: 14px;
        }
        .status {
            margin-bottom: 12px;
            color: #6ee7b7;
            font-size: 14px;
        }
        .theme-premium {
            --bg: #0d1a2f;
            --panel: #121f35;
            --panel2: #1b2f4a;
            --accent: linear-gradient(90deg, #c79b3a, #f2d98a);
            --text: #f4efe2;
            --muted: #d5c6a2;
            --danger: #ff9f9f;
            --input-bg: #0f1f33;
            --input-border: #705a2f;
            --btn-text: #1b1405;
        }
        body.theme-premium {
            background:
                radial-gradient(circle at 20% 10%, rgba(240, 197, 99, .18) 0, transparent 32%),
                radial-gradient(circle at 80% 90%, rgba(240, 197, 99, .12) 0, transparent 28%),
                linear-gradient(140deg, #0d1a2f 0%, #08101f 100%);
        }
        body.theme-premium .card {
            border: 1px solid #7b6230;
            box-shadow: 0 18px 60px rgba(0, 0, 0, .55), inset 0 0 0 1px rgba(242, 217, 138, .18);
        }
        body.theme-premium input:focus {
            border-color: #d8b86c;
            box-shadow: 0 0 0 3px rgba(216, 184, 108, .18);
        }
        body.theme-premium button {
            background: var(--accent);
        }
    </style>
</head>
<body>
<div class="page">
    <div class="template-row">
        <select id="templatePicker">
            <option value="modern">Template Modern Blue</option>
            <option value="premium">Template Premium Elegant</option>
        </select>
    </div>
    <main class="card">
        <div class="brand-wrap">
            <img class="brand-logo" src="{{ asset('assets/sinyal-saham-logo.svg') }}" alt="Sinyal Saham Indo">
            <p class="brand-title">Sinyal Saham Indo</p>
        </div>
        <h1>Admin Login</h1>
        <p>Sinyal Saham Indo Backend</p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Masuk</button>
        </form>

        @error('email')
            <div class="error">{{ $message }}</div>
        @enderror
    </main>
</div>

<script>
    (function () {
        const key = 'ssi_login_theme';
        const picker = document.getElementById('templatePicker');
        const saved = localStorage.getItem(key) || 'modern';
        picker.value = saved;

        const applyTheme = (val) => {
            document.body.classList.toggle('theme-premium', val === 'premium');
        };

        applyTheme(saved);
        picker.addEventListener('change', function () {
            localStorage.setItem(key, this.value);
            applyTheme(this.value);
        });
    })();
</script>
</body>
</html>
