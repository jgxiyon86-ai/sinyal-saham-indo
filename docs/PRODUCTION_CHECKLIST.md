# Production Checklist (Sinyal Saham Indo + ALIMA Gateway)

## 1) Sinyal Saham Indo (.env)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://saham.pondokalima.com` (atau domain produksi Anda)
- `ALIMA_GATEWAY_BASE_URL=https://hubku.cuanholic.com` (domain gateway)
- `ALIMA_GATEWAY_APP_API_KEY=<api-key-app-di-alima>`
- `ALIMA_GATEWAY_SESSION_ID=<session-id-device-aktif>`

## 2) Storage untuk Upload Gambar
Jalankan di server `sinyal-saham-indo`:

```bash
php artisan storage:link
```

Tanpa ini, upload gambar manual/template tidak bisa diakses publik.

## 3) Cache & Migration
Jalankan saat deploy:

```bash
php artisan migrate --force
php artisan optimize
```

## 4) Cron Job Scheduler
Pastikan scheduler aktif:

```bash
* * * * * cd /home/USER/PATH/sinyal-saham-indo && /usr/local/bin/ea-php82 artisan schedule:run >> /dev/null 2>&1
```

Ganti `ea-php82` sesuai versi PHP server.

## 5) WA Gateway (ALIMA)
- Session device harus status `connected`.
- App harus terkunci ke app ID yang benar.
- API key app dari ALIMA harus sama dengan `.env` aplikasi sinyal.

## 6) Test Akhir Setelah Deploy
1. Buat template dengan gambar.
2. Jalankan manual send pakai upload gambar.
3. Jalankan preview + kirim blast.
4. Cek log WA Blast dan message history di ALIMA.

