<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Akun Klient Sinyal Saham Indo</title>
</head>
<body style="font-family:Arial,sans-serif;color:#1a1a1a;line-height:1.5;">
<h2 style="margin:0 0 12px;">Akun Klient Sinyal Saham Indo</h2>
<p>Halo {{ $client->name }},</p>
<p>Berikut data login aplikasi Android kamu:</p>
<ul>
    <li><b>Username / Email:</b> {{ $client->email }}</li>
    <li><b>Password:</b> {{ $plainPassword }}</li>
</ul>
<p>Silakan login di aplikasi dan segera ubah password jika diperlukan.</p>
<p>Salam,<br>Tim Sinyal Saham Indo</p>
</body>
</html>

