# Website & Backoffice RSU Serenapita Sidikalang (Laravel 13)

Paket ini berisi file aplikasi yang ditimpakan ke proyek Laravel baru. Tidak perlu Node.js/npm.

## Kebutuhan
- PHP 8.3 atau lebih baru dengan ekstensi: mbstring, openssl, pdo_sqlite (atau pdo_mysql), fileinfo, curl, xml, ctype, tokenizer
- Composer 2
- Windows: paling mudah memakai Laragon (sudah berisi PHP dan Composer)

## Instalasi
1. Buat proyek Laravel baru
   ```
   composer create-project laravel/laravel rsu-serenapita
   cd rsu-serenapita
   ```
2. Salin seluruh isi paket ini ke folder `rsu-serenapita`, timpa file yang sama.
3. Ubah `.env` (ganti baris yang ada, tambahkan sisanya di bawah):
   ```
   APP_NAME="RSU Serenapita Sidikalang"
   APP_URL=http://127.0.0.1:8000
   APP_LOCALE=id

   SIMRS_DRIVER=fake
   SIMRS_BASE_URL=
   SIMRS_TOKEN=
   SIMRS_WEBHOOK_SECRET=ganti-dengan-teks-acak-panjang
   SIMRS_TIMEOUT=15

   LIVECHAT_VISITOR_POLL_MS=2500
   LIVECHAT_AGENT_POLL_MS=2000
   ```
4. Buka `config/app.php`, ubah `'timezone' => 'UTC'` menjadi `'timezone' => 'Asia/Jakarta'`.
5. Isi database dan siapkan folder upload:
   ```
   php artisan migrate:fresh --seed
   php artisan storage:link
   ```
6. Jalankan:
   ```
   php artisan serve
   ```
   - Situs: http://127.0.0.1:8000
   - Backoffice: http://127.0.0.1:8000/admin/login
   - Akun: admin@rsuserenapita.co.id / serenapita123 (segera ganti di menu Pengguna)
   - Akun contoh lain: humas@… dan pendaftaran@… dengan kata sandi yang sama
7. (Opsional) Sinkron SIMRS otomatis, di terminal kedua:
   ```
   php artisan schedule:work
   ```
   Di server produksi, pasang cron: `* * * * * cd /path/proyek && php artisan schedule:run >> /dev/null 2>&1`

### Memakai MySQL
Buat database `rsu_serenapita`, lalu di `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rsu_serenapita
DB_USERNAME=root
DB_PASSWORD=
```
Kemudian jalankan langkah 5.

## Menyambungkan SIMRS asli
- Ubah `SIMRS_DRIVER=http`, isi `SIMRS_BASE_URL` (alamat API gateway) dan `SIMRS_TOKEN`.
- Sesuaikan nama endpoint dan field di `app/Services/Simrs/HttpSimrsClient.php` dengan dokumentasi vendor SIMRS:
  `GET /clinics`, `GET /doctors`, `GET /leaves`, `GET /slots?doctor=&date=`, `POST /bookings`, `GET /bookings/{code}`, `GET /ping`.
- Webhook dari SIMRS: `POST /api/simrs/webhook` dengan header `X-Simrs-Secret: <SIMRS_WEBHOOK_SECRET>`.
  Body `{"event":"schedule"}` menjalankan sinkron, atau `{"event":"appointment","kode_booking":"...","status":"Hadir"}` memperbarui status janji.
- Uji dari backoffice: menu Integrasi SIMRS → Uji koneksi → Sinkronkan sekarang.

## Daftar URL
Situs: `/`, `/news`, `/news/{slug}`, `/doctors`, `/schedule`, `/services`, `/contact`, `/appointments`

Backoffice: `/admin/login`, `/admin/dashboard`, `/admin/livechat`, `/admin/news`, `/admin/categories`, `/admin/slides`, `/admin/info`, `/admin/services`, `/admin/doctors`, `/admin/clinics`, `/admin/schedule`, `/admin/appointments`, `/admin/appointment-form`, `/admin/menu`, `/admin/chatbot`, `/admin/profile`, `/admin/simrs`, `/admin/users`, `/admin/roles`, `/admin/logs`

## Catatan
- Live chat memakai polling 2–2,5 detik sehingga langsung jalan dengan `php artisan serve`. Untuk realtime penuh dapat ditingkatkan ke Laravel Reverb.
- Chatbot Serena berbasis aturan kata kunci, tanpa layanan AI luar dan tanpa biaya. Jawaban dibentuk dari data database (jadwal, cuti, poli, layanan, kontak) dan FAQ yang diisi di menu Chatbot.
- Nama dokter, nomor telepon, dan berita adalah data contoh; ganti dari backoffice.
- Produksi: set `APP_ENV=production`, `APP_DEBUG=false`, pakai HTTPS, lalu `php artisan optimize`.

## Struktur penting
- `app/Admin/Resources.php` — definisi CRUD backoffice (berita, kategori, slider, layanan, dokter, poli, janji, pengguna)
- `app/Services/Simrs/` — integrasi SIMRS (fake & http) dan sinkronisasi
- `app/Services/Chatbot.php` — chatbot Serena (aturan kata kunci, sinonim, salah ketik ringan)
- `app/Http/Controllers/Site|Admin` — controller situs & backoffice
- `resources/views/site|admin` — tampilan; `public/css`, `public/js` — gaya & skrip
