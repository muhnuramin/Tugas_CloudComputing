# ☁️ Tugas Besar Cloud Computing

## Anggota Kelompok
1. **M. Syihabul Alam** (240602008)
2. **M. Nur Amin** (260602001)
3. **Dimas Candra** (240602001)
4. **M. Askhabur Rida'** (240602007)

---

## 🚀 Menjalankan Aplikasi dengan Docker

### Prasyarat
- Docker Desktop dengan Docker Compose aktif.
- Token bot Telegram dari BotFather. Simpan token hanya di file `.env` lokal, jangan di README atau commit.

### 1. Siapkan konfigurasi

Jalankan dari folder utama repositori menggunakan PowerShell:

```powershell
Copy-Item "telegramgateway(service2)\.env.example" "telegramgateway(service2)\.env"
Copy-Item "siakad(service1)\.env.example" "siakad(service1)\.env"
notepad "telegramgateway(service2)\.env"
notepad "siakad(service1)\.env"
```

Isi `TELEGRAM_BOT_TOKEN` di kedua file `.env` lokal dengan token yang sama. SIAKAD memakai tokennya untuk mengirim langsung ke Telegram Bot API dari queue worker; Gateway tetap tersedia sebagai fallback. Atur juga `TELEGRAM_NOTIFICATION_CHAT_ID` di `.env` SIAKAD untuk tujuan grup. Jangan menyimpan token atau Chat ID di Git. Jika token bot pernah masuk ke Git, cabut dan buat token baru melalui BotFather.

### 2. Pasang dependensi SIAKAD

```powershell
Set-Location "siakad(service1)"
docker run --rm -v "${PWD}:/var/www/html" -w /var/www/html laravelsail/php84-composer:latest composer install
```

### 3. Jalankan Telegram Gateway

```powershell
Set-Location "..\telegramgateway(service2)"
docker compose up -d --build
```

Gateway dapat diperiksa di [http://localhost:8002/docs](http://localhost:8002/docs).

### 4. Jalankan SIAKAD dan worker antrean

```powershell
Set-Location "..\siakad(service1)"
docker compose up -d mysql laravel.test
docker compose exec laravel.test php artisan key:generate
docker compose exec laravel.test php artisan migrate --seed
docker compose up -d laravel.queue
```

SIAKAD tersedia di [http://localhost:8080](http://localhost:8080). Worker `laravel.queue` memproses notifikasi Telegram di latar belakang; biarkan container tersebut tetap berjalan.

Jika port host `8080` atau `3306` sudah dipakai, pilih port kosong di `.env` SIAKAD dengan mengubah `APP_PORT`, `APP_URL`, dan `FORWARD_DB_PORT`. Pada mesin ini, `.env` lokal menggunakan `APP_PORT=8081`, `APP_URL=http://localhost:8081`, dan `FORWARD_DB_PORT=3308`.

### 5. Aktifkan notifikasi Telegram

1. Buka bot Telegram yang tokennya Anda konfigurasi dan tekan **Start**.
2. Tambahkan bot ke grup Telegram privat yang anggotanya berwenang melihat notifikasi akademik dan detail login.
3. Isi `TELEGRAM_NOTIFICATION_CHAT_ID` pada `.env` SIAKAD dengan Chat ID grup, lalu restart aplikasi dan worker dengan `docker compose restart laravel.test laravel.queue`. Jangan simpan Chat ID grup atau token bot di Git.
4. Jika `TELEGRAM_NOTIFICATION_CHAT_ID` diatur, notifikasi login, perubahan jadwal, status KRS, dan pembaruan nilai diarahkan ke grup; nama penerima disertakan pada notifikasi akademik. Jika kosong, notifikasi aplikasi dikirim ke Chat ID personal pada profil pengguna.
5. Uji dengan logout lalu login kembali, kemudian lakukan perubahan nilai, KRS, atau jadwal.

Pastikan queue worker dan koneksi internet aktif. Jika token SIAKAD kosong, notifikasi memakai Gateway. Pengiriman tetap asinkron; worker memeriksa antrean setiap detik, jadi jeda sekitar satu detik plus waktu respons Telegram masih mungkin terjadi.

Lihat [PANDUAN_DOCKER.md](PANDUAN_DOCKER.md) untuk akun demo, arsitektur, dan perintah pemeliharaan yang lebih lengkap.
