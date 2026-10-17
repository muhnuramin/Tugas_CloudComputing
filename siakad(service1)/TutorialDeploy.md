# Panduan Deploy SIAKAD ke Docker Server (Windows Server & Linux VPS)

Dokumen ini berisi panduan lengkap langkah demi langkah untuk melakukan deploy aplikasi **SIAKAD (Sistem Informasi Akademik)** ke server menggunakan **Docker Desktop di Windows Server** maupun **Docker di Linux Server (VPS)**.

---

## Daftar Isi
1. [Arsitektur Container Production](#1-arsitektur-container-production)
2. [Panduan Deploy ke Windows Server (Docker Desktop)](#2-panduan-deploy-ke-windows-server-docker-desktop)
   - [A. Prasyarat & Pengaturan Docker Desktop](#a-prasyarat--pengaturan-docker-desktop)
   - [B. Menyiapkan File Proyek](#b-menyiapkan-file-proyek)
   - [C. Konfigurasi Environment (.env)](#c-konfigurasi-environment-env)
   - [D. Menjalankan Container (PowerShell)](#d-menjalankan-container-powershell)
   - [E. Inisialisasi Database & Cache Laravel](#e-inisialisasi-database--cache-laravel)
   - [F. Membuka Port di Windows Firewall](#f-membuka-port-di-windows-firewall)
   - [G. Mengakses Aplikasi dari Komputer Client (LAN)](#g-mengakses-aplikasi-dari-komputer-client-lan)
3. [Panduan Deploy ke Linux Server (VPS Ubuntu/Debian)](#3-panduan-deploy-ke-linux-server-vps-ubuntudebian)
4. [Prosedur Pembaruan Aplikasi (Update Workflow)](#4-prosedur-pembaruan-aplikasi-update-workflow)
5. [Perintah Pemeliharaan Harian (Maintenance)](#5-perintah-pemeliharaan-harian-maintenance)
6. [Troubleshooting & Solusi Masalah Umum](#6-troubleshooting--solusi-masalah-umum)

---

## 1. Arsitektur Container Production

Proyek ini menggunakan konfigurasi multi-container berbasis `docker-compose.prod.yml`:

| Service | Container Name | Image / Base | Fungsi |
|---|---|---|---|
| **web** | `siakad-prod-web` | `nginx:alpine` | Web Server Reverse Proxy (Port 80/443), Gzip, Static Cache |
| **app** | `siakad-prod-app` | PHP 8.4-FPM Alpine | Backend Laravel 12 + OPcache + Asset Frontend Vite |
| **queue** | `siakad-prod-queue` | PHP 8.4-FPM Alpine | Worker pemroses background jobs / antrean asynchronous |
| **db** | `siakad-prod-db` | `mysql:8.0` | Database Server MySQL (Port 3306) |

---

## 2. Panduan Deploy ke Windows Server (Docker Desktop)

### A. Prasyarat & Pengaturan Docker Desktop

1. Pastikan **WSL 2** sudah aktif di Windows Server:
   ```powershell
   wsl --install
   ```
2. Unduh dan pasang [Docker Desktop for Windows](https://www.docker.com/products/docker-desktop/).
3. Buka **Docker Desktop**, lalu masuk ke menu **Settings (ikon gear ⚙️)**:
   - **General**:
     - Centang: `Start Docker Desktop when you log in`
     - Pastikan terpilih: `Use the WSL 2 based engine`
   - Klik **Apply & restart**.
4. Pastikan status Docker Desktop di pojok kiri bawah sudah **Engine running** (warna hijau).

---

### B. Menyiapkan File Proyek

Letakkan seluruh folder proyek di direktori Windows Server, misalnya di `C:\siakad`:

- **Opsi 1 (Menggunakan Git - Direkomendasikan):**
  Buka PowerShell dan jalankan:
  ```powershell
  cd C:\
  git clone https://github.com/ryandaaa/siakad.git C:\siakad
  cd C:\siakad
  ```
- **Opsi 2 (Salin Manual):**
  Salin folder proyek Anda dari komputer lokal ke `C:\siakad` di Windows Server.

---

### C. Konfigurasi Environment (.env)

Di dalam direktori `C:\siakad`, buat file `.env` khusus server:

```powershell
Copy-Item .env.example .env
```

Buka file `.env` dengan Notepad (`notepad .env`) dan sesuaikan pengaturan produksi:

```env
APP_NAME=SIAKAD
APP_ENV=production
APP_DEBUG=false
# Ganti dengan IP LAN Windows Server Anda atau domain institusi:
APP_URL=http://192.168.1.100

APP_LOCALE=id
APP_FALLBACK_LOCALE=en

# DATABASE DOCKER PRODUCTION
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=siakad
DB_USERNAME=siakad_user
DB_PASSWORD=GantiPasswordKuatUser123!
DB_ROOT_PASSWORD=GantiPasswordKuatRoot123!

# SESSION & CACHE
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# PORT MAPPING (Opsional, default 80 dan 443)
HTTP_PORT=80
HTTPS_PORT=443
```

> **PERINGATAN KEAMANAN:**
> - Pastikan `APP_DEBUG=false`.
> - Gunakan password database yang kuat dan acak.
> - `DB_HOST` harus bernilai `db` (merujuk ke nama service database di Docker Compose).

---

### D. Menjalankan Container (PowerShell)

Buka **PowerShell sebagai Administrator**, arahkan ke folder proyek, dan jalankan perintah:

```powershell
cd C:\siakad

# Membangun image dan menyalakan seluruh service
docker compose -f docker-compose.prod.yml up -d --build
```

Tunggu proses build selesai hingga seluruh container berstatus `Started` atau `Healthy`. Verifikasi dengan:
```powershell
docker ps
```

---

### E. Inisialisasi Database & Cache Laravel

Jalankan perintah berikut satu per satu di PowerShell:

```powershell
# 1. Generate Application Encryption Key
docker compose -f docker-compose.prod.yml exec app php artisan key:generate

# 2. Eksekusi Migrasi Tabel Database
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 3. (Opsional) Mengisi Data Master & Akun Awal jika server baru
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force

# 4. Membuat Symbolic Link Storage Upload
docker compose -f docker-compose.prod.yml exec app php artisan storage:link

# 5. Mengaktifkan Cache Konfigurasi & Performa (Wajib di Production)
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache
docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

---

### F. Membuka Port di Windows Firewall

Agar komputer lain di jaringan LAN / Kampus bisa mengakses web SIAKAD di server, buka port HTTP (Port 80) di Windows Defender Firewall:

Jalankan perintah ini di **PowerShell Administrator**:
```powershell
New-NetFirewallRule -DisplayName "SIAKAD Web Server Port 80" -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow
```

*(Jika nanti menggunakan HTTPS port 443, jalankan juga):*
```powershell
New-NetFirewallRule -DisplayName "SIAKAD Web Server Port 443" -Direction Inbound -Protocol TCP -LocalPort 443 -Action Allow
```

---

### G. Mengakses Aplikasi dari Komputer Client (LAN)

1. Cek IP Address Windows Server Anda:
   ```powershell
   ipconfig
   ```
   Cari baris `IPv4 Address`, contohnya: `192.168.1.100`.

2. Buka browser di komputer dosen, mahasiswa, atau admin di jaringan yang sama:
   ```text
   http://192.168.1.100
   ```

3. **Akun Pengujian Awal (jika menjalankan db:seed):**
   - **Superadmin**: `superadmin@siakad.test` (password: `password`)
   - **Admin Fakultas**: `admin.ftik@siakad.test` (password: `password`)
   - **Dosen**: `dosen@siakad.test` (password: `password`)
   - **Mahasiswa**: `mahasiswa@siakad.test` (password: `password`)

---

## 3. Panduan Deploy ke Linux Server (VPS Ubuntu/Debian)

Jika di masa mendatang Anda ingin memindahkan sistem ini ke VPS Linux di cloud (DigitalOcean, AWS, GCP, dsb.):

```bash
# 1. Hubungkan ke VPS
ssh root@ip-vps-anda

# 2. Pasang Docker & Docker Compose
curl -fsSL https://get.docker.com -o get-docker.sh && sh get-docker.sh

# 3. Clone Repository
git clone <URL_REPOSITORY> /var/www/siakad
cd /var/www/siakad

# 4. Buat file .env dan sesuaikan nilainya
cp .env.example .env
nano .env

# 5. Jalankan Container
docker compose -f docker-compose.prod.yml up -d --build

# 6. Inisialisasi Laravel
docker compose -f docker-compose.prod.yml exec app php artisan key:generate
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan storage:link
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

---

## 4. Prosedur Pembaruan Aplikasi (Update Workflow)

Ketika ada update kode atau fitur baru dari tim pengembang, lakukan pembaruan di server dengan langkah berikut:

```powershell
cd C:\siakad

# 1. Tarik pembaruan kode terbaru
git pull origin main

# 2. Build ulang container (otomatis compile frontend dan update vendor)
docker compose -f docker-compose.prod.yml up -d --build

# 3. Jalankan migrasi database baru jika ada perubahan tabel
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# 4. Refresh cache konfigurasi
docker compose -f docker-compose.prod.yml exec app php artisan optimize
```

---

## 5. Perintah Pemeliharaan Harian (Maintenance)

- **Melihat status container:**
  ```powershell
  docker compose -f docker-compose.prod.yml ps
  ```

- **Melihat log container (realtime):**
  ```powershell
  # Semua log
  docker compose -f docker-compose.prod.yml logs -f

  # Khusus log aplikasi Laravel
  docker compose -f docker-compose.prod.yml logs -f app

  # Khusus log Nginx
  docker compose -f docker-compose.prod.yml logs -f web
  ```

- **Menghentikan aplikasi:**
  ```powershell
  docker compose -f docker-compose.prod.yml stop
  ```

- **Menjalankan kembali aplikasi yang dihentikan:**
  ```powershell
  docker compose -f docker-compose.prod.yml start
  ```

- **Masuk ke dalam terminal container aplikasi (bash/sh):**
  ```powershell
  docker compose -f docker-compose.prod.yml exec app sh
  ```

- **Backup Database MySQL:**
  ```powershell
  docker compose -f docker-compose.prod.yml exec db mysqldump -u siakad_user -pGantiPasswordKuatUser123! siakad > backup_siakad.sql
  ```

---

## 6. Troubleshooting & Solusi Masalah Umum

### 1. Error: "Port 80 is already in use"
* **Penyebab**: Windows Server sudah memiliki IIS (Internet Information Services) atau Apache XAMPP yang aktif di Port 80.
* **Solusi**:
  - Matikan IIS: Buka PowerShell Administrator dan jalankan: `Stop-Service W3SVC` dan `Set-Service -Name W3SVC -StartupType Disabled`.
  - Atau ganti port HTTP di file `.env`:
    ```env
    HTTP_PORT=8080
    ```
    Lalu akses aplikasi melalui `http://IP-SERVER:8080`.

### 2. Error: "failed to connect to the docker API"
* **Penyebab**: Aplikasi Docker Desktop belum terbuka atau service Docker Desktop Engine mati.
* **Solusi**: Buka aplikasi Docker Desktop dari Start Menu Windows dan tunggu hingga status bertuliskan *Engine running*.

### 3. Komputer di jaringan LAN tidak bisa membuka web
* **Penyebab**: Port 80 diblokir oleh Windows Defender Firewall atau tipe jaringan diset ke *Public*.
* **Solusi**:
  - Pastikan aturan firewall sudah ditambahkan (lihat [Bagian F](#f-membuka-port-di-windows-firewall)).
  - Ubah profil koneksi jaringan Windows dari *Public* ke *Private*.

### 4. Docker Desktop mati saat Windows Server restart
* **Solusi**:
  - Centang opsi *Start Docker Desktop when you log in* pada pengaturan Docker Desktop.
  - Gunakan utilitas resmi Microsoft [Autologon](https://learn.microsoft.com/en-us/sysinternals/downloads/autologon) agar akun server otomatis login ke desktop saat booting.
