<a id="cl-masuk" class="anchor"></a>

# Bab 2. Masuk dan menu

## 2.1 Masuk ke backoffice

1. Buka alamat backoffice website rumah sakit, lalu tambahkan `/admin/login`.
2. Isi email akun Akunting: `akunting@rsuserenapita.id`.
3. Isi kata sandi. Kata sandi awal: `Akunting@Serenapita26`.
4. Saat pertama kali masuk, sistem meminta kata sandi diganti. Buat kata sandi baru yang kuat dan simpan dengan aman.

Kata sandi bisa diganti kapan saja lewat ikon kunci di pojok kiri bawah, di sebelah nama akun.

<div class="callout"><b>Lupa kata sandi</b><p>Minta tim IT menjalankan perintah <code>php artisan dashboard:reset-password akunting@rsuserenapita.id</code> di server website. Perintah ini menampilkan kata sandi sementara, yang wajib diganti saat masuk.</p></div>

## 2.2 Menu untuk Akunting

Di sisi kiri, di bawah kelompok **Manajemen**, ada tiga menu:

| Menu | Isi |
|---|---|
| **Dasbor manajemen** | Laporan keuangan harian dari Khanza (pembayaran, piutang, hutang, klaim BPJS). Tidak dibahas di panduan ini. |
| **Closing akuntansi** | Modul closing yang dibahas di panduan ini. |
| **Panduan akunting** | Panduan ini. |

## 2.3 Bagian atas setiap halaman closing

Setiap halaman Closing akuntansi menampilkan:

- **Penanda status**:
  - tanggal cut off dan tanggal mulai SIMRS baru;
  - awal tahun buku;
  - waktu data Khanza terakhir diambil. Bila berwarna kuning dengan tulisan *perlu diambil ulang*, pengaturan sudah berubah sejak data diambil.
  - tanda **Dikunci** bila closing sudah dikunci.
- **Panduan halaman ini**: membuka bab panduan yang sesuai dengan halaman yang sedang dibuka.
- **Tab halaman**: Ringkasan, COA baru, Pemetaan akun, Neraca saldo, Laporan keuangan, Rekonsiliasi, Ranap lanjutan, Jurnal penyesuaian.
- **Tombol unduh** di kanan atas, berbeda di setiap halaman.
