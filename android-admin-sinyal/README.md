# Android Admin Sinyal (Project Baru)

Project admin Android yang terpisah dari app client.

## Fitur
- Login khusus admin (`/api/auth/login`)
- Input sinyal baru (`/api/admin/signals`)
- Kirim WA blast per daftar signal id (`/api/admin/signals/wa-blast`)
- Dashboard premium (card style modern)
- List sinyal realtime (auto refresh 20 detik)
- Tombol blast langsung per item sinyal

## Konfigurasi URL Server
Edit file `app/build.gradle.kts` bagian `buildConfigField("String", "BASE_URL", ...)`.

Contoh production:
- `https://sinyal.cuanholic.com/api/`

## Build (Android Studio)
1. Open folder `android-admin-sinyal`.
2. Sync Gradle.
3. Run ke device Android.

Jika `adb` tidak di PATH, gunakan:
- `C:\Users\EOA\AppData\Local\Android\Sdk\platform-tools\adb.exe devices`

## Catatan API
Payload create signal yang dipakai app:
- `title`, `stock_code`, `signal_type`, `entry_price`, `take_profit`, `stop_loss`, `note`, `image_url`, `tier_ids[]`

Payload WA blast:
- `signal_ids[]`
- `tier_id` (opsional)
