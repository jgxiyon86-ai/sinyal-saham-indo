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
        }
        .card {
            width: 100%;
            max-width: 430px;
            background: linear-gradient(145deg, var(--panel), var(--panel2));
            border: 1px solid #64b8ff;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
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
            border: 1px solid #3b5f6a;
            background: #0b3f7d;
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
            color: #042443;
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
        .hint {
            margin-top: 14px;
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>
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

    <div class="hint">Default: admin@sinyalsahamindo.local / admin12345</div>
</main>
</body>
</html>
