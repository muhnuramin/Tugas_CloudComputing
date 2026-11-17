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
```

Isi `TELEGRAM_BOT_TOKEN` di file `.env` gateway dengan token bot Anda. Template SIAKAD sudah mengatur URL gateway Docker ke `http://host.docker.internal:8002`. Jika token bot pernah masuk ke Git, cabut dan buat token baru melalui BotFather.

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
2. Dapatkan Chat ID Anda melalui bot `/start` jika tersedia, atau melalui `@userinfobot`.
3. Login ke SIAKAD, buka **Profil**, isi **Telegram Chat ID**, lalu simpan.
4. Uji dengan logout dan login lagi. Notifikasi juga dikirim untuk perubahan jadwal, persetujuan/penolakan KRS, dan pembaruan nilai.

Pastikan queue worker dan Gateway aktif. Pengiriman dilakukan secara asinkron, tetapi waktu sampai tetap bergantung pada koneksi internet dan layanan Telegram.

Lihat [PANDUAN_DOCKER.md](PANDUAN_DOCKER.md) untuk akun demo, arsitektur, dan perintah pemeliharaan yang lebih lengkap.
