# Android Client - Sinyal Saham Indo

## Fitur utama
- Login client ke backend Laravel (`/api/auth/login`)
- Splash screen auto route ke Login/Main
- Halaman depan model percakapan sinyal
- Hard alert saat ada sinyal baru:
  - getar
  - suara hard tone + alarm channel
  - notifikasi percakapan (MessagingStyle)
- Polling realtime saat app terbuka (20 detik)
- Background sync via WorkManager (15 menit)
- Firebase Cloud Messaging receiver siap pakai

## Base URL
Default sekarang langsung ke server production:
- `https://sinyal.cuanholic.com/api/`

Jika ingin debug lokal di emulator/device, ganti `BASE_URL` di `app/build.gradle.kts` ke IP lokal.

## Jalankan (Android Studio)
1. Buka folder `android-client` di Android Studio.
2. Sync Gradle.
3. Run ke emulator/device.

## Debug langsung di HP Android
1. Aktifkan `Developer options` dan `USB debugging` di HP.
2. Sambungkan HP via USB, izinkan debugging.
3. Jika backend di laptop yang sama:
   - pastikan HP dan laptop satu Wi-Fi (untuk akses IP lokal), atau gunakan USB reverse.
4. Ubah `BASE_URL` di `app/build.gradle.kts` ke IP laptop, contoh:
   - `http://192.168.1.20:8082/api/`
5. Sync dan Run dari Android Studio ke device kamu.

### Opsi Debug via Wi-Fi (ADB Wireless)
1. Sambungkan HP ke USB sekali.
2. Jalankan:
   - `adb tcpip 5555`
3. Cari IP HP (mis. `192.168.1.30`), lalu jalankan:
   - `adb connect 192.168.1.30:5555`
4. Cabut USB, lalu Run dari Android Studio (device tetap muncul via Wi-Fi).

## Firebase push setup
1. Buat project di Firebase Console.
2. Tambahkan app Android package: `com.alima.sinyalsahamindo`.
3. Download `google-services.json`.
4. Taruh file ke:
   - `android-client/app/google-services.json`
5. Setelah itu rebuild app.
6. Di backend Laravel isi `.env`:
   - `FCM_SERVER_KEY=YOUR_FCM_SERVER_KEY`

## Catatan
- Android 13+ minta izin notifikasi saat login.
- Akun yang dipakai harus role `client`.
- Jika sinyal di backend dihapus atau melewati `expires_at`, pada refresh berikutnya sinyal akan hilang dari app.

## Build APK Release (Signed)
1. Android Studio -> `Build` -> `Generate Signed Bundle / APK`.
2. Pilih `APK`.
3. Buat / pilih `keystore` (`.jks`), isi alias + password.
4. Pilih varian `release`.
5. Klik `Finish`.
6. File hasil ada di:
   - `android-client/app/release/app-release.apk`
