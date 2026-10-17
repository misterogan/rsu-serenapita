DASBOR MANAJEMEN (DATA SIMRS KHANZA) - CATATAN UNTUK TIM IT
===========================================================

Dasbor manajemen hanya bisa dibuka oleh 3 akun berikut. Akun ini tidak terlihat
di menu Pengguna, Hak akses, maupun Log aktivitas milik pengelola konten website.

  Komisaris  komisaris@rsuserenapita.id  semua bagian dasbor
  Direktur   direktur@rsuserenapita.id   operasional saja (tanpa keuangan)
  Akunting   akunting@rsuserenapita.id   keuangan / billing saja

Kata sandi awal (wajib diganti saat pertama kali masuk):
  Komisaris@Serenapita26, Direktur@Serenapita26, Akunting@Serenapita26

Lupa kata sandi? Jalankan di server website:
  php artisan dashboard:reset-password direktur@rsuserenapita.id
Perintah ini menampilkan kata sandi sementara baru.

KONEKSI KE DATABASE KHANZA
1. Di server Khanza, buat user yang hanya boleh membaca:
     CREATE USER 'dashboard'@'IP_SERVER_WEBSITE' IDENTIFIED BY 'password-kuat';
     GRANT SELECT ON sik.* TO 'dashboard'@'IP_SERVER_WEBSITE';
2. Pastikan server website bisa menjangkau port database Khanza (biasanya 3306).
3. Tambahkan ke .env server website:
     KHANZA_DB_HOST=ip-server-khanza
     KHANZA_DB_PORT=3306
     KHANZA_DB_DATABASE=sik
     KHANZA_DB_USERNAME=dashboard
     KHANZA_DB_PASSWORD=password-kuat
     KHANZA_POLI_IGD=IGDK            (kode poli IGD di tabel poliklinik)
     KHANZA_CACHE_MINUTES=10         (opsional)
     KHANZA_QUERY_TIMEOUT=30         (opsional, detik per query)
4. Jalankan: php artisan config:clear

ISI DASBOR (per tab, dimuat saat tab dibuka)
  Ringkasan           peringatan otomatis, kartu utama, indikator mutu, target vs realisasi,
                      perbandingan tahun lalu, tren 12 bulan kunjungan dan pendapatan
  Pelayanan           tren harian, poli (klik untuk rincian dokter dan diagnosa), cara bayar,
                      kinerja dokter, ketepatan jam praktik, jam sibuk, antrean online,
                      diagnosa, rujukan masuk, wilayah, demografi
  Rawat inap          BOR/ALOS/TOI/BTO/GDR/NDR, dirawat ulang <= 30 hari, okupansi, status pulang, DPJP
  Penunjang & farmasi lama hasil lab/radiologi, waktu tunggu resep, lab per kategori, operasi,
                      obat terbanyak, stok di bawah minimal, obat kedaluwarsa
  Keuangan            pembayaran, piutang baru, umur piutang, pelunasan, pendapatan per poli dan dokter,
                      komposisi tagihan, jasa tindakan dokter, hutang supplier, pengeluaran harian,
                      margin farmasi
  Klaim BPJS          tarif INA-CBG dibandingkan biaya RS per CBG

Hak akses: Komisaris semua tab dan bisa mengisi target bulanan; Direktur tab Ringkasan,
Pelayanan, Rawat inap, Penunjang (tanpa rupiah); Akunting tab Ringkasan, Keuangan, Klaim BPJS.

Semua query ada di laporan-manajemen.sql (folder ini) dan di
app/Services/Khanza/KhanzaReports.php. Bila ada laporan yang tidak bisa dibaca
karena versi Khanza berbeda, dasbor menampilkan pesan errornya.
