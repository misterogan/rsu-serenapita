<a id="patients" class="anchor"></a>

# Bab 32. Data pasien

<p class="lead">Menu <b>Rekam medis → Data pasien</b> menampilkan identitas dan riwayat berobat pasien langsung dari database SIMRS Khanza, sehingga petugas tidak perlu membuka dua aplikasi. Menu ini <b>hanya untuk melihat</b>; perubahan data tetap dilakukan di SIMRS.</p>

Akun khusus: `datapasien@rsuserenapita.id` dengan kata sandi awal `Pasien@Serenapita26` (wajib diganti saat pertama kali masuk). Peran **Data Pasien** hanya bisa membuka menu ini. Petugas lain bisa diberi akses lewat **Hak akses** dengan mencentang *Data pasien*.

<div class="callout penting"><b>Kerahasiaan data pasien</b><p>Data rekam medis bersifat rahasia. Setiap pencarian dan setiap pembukaan data pasien tercatat di <b>Log aktivitas</b> beserta nama akun dan waktunya. Gunakan hanya untuk keperluan pelayanan, jangan bagikan akun, dan keluar dari backoffice setelah selesai.</p></div>

<a id="patients-1" class="anchor"></a>

## 32.1 Mencari pasien

1. Buka **Data pasien**. Tanpa pencarian, tampil daftar pasien yang mendaftar **hari ini**; pakai panah atau kotak tanggal untuk hari lain.
2. Ketik **No. RM**, **NIK**, **No. kartu BPJS**, **nomor HP**, atau **nama** (boleh sebagian, misalnya *maria sitorus*) lalu tekan Enter. Minimal 3 huruf atau angka. Hasil dibatasi 50 pasien; persempit kata bila terlalu banyak.
3. Klik baris pasien untuk membuka profilnya.

<a id="patients-2" class="anchor"></a>

## 32.2 Profil pasien

| Bagian | Isi |
|---|---|
| Riwayat alergi | Kotak merah di atas bila pernah tercatat alergi pada pemeriksaan rawat jalan. |
| Identitas | No. RM, NIK, tempat dan tanggal lahir, umur, golongan darah, agama, pendidikan, pekerjaan, alamat lengkap, telepon, ibu kandung, penanggung jawab, cara bayar, dan No. BPJS. |
| Ringkasan | Jumlah kunjungan, rawat inap, kunjungan pertama dan terakhir. |
| Tanda vital terakhir | Tensi, nadi, napas, suhu, SpO2, berat, tinggi, GCS dari pemeriksaan terakhir. |
| Diagnosa tersering | Lima diagnosa (ICD-10) yang paling sering tercatat. |
| Riwayat kunjungan | Semua kunjungan dari yang terbaru: tanggal, rawat jalan / inap dan ruangnya, poli, dokter, diagnosa utama, penjamin, status. Bisa disaring per jenis, tahun, atau kata. |

<a id="patients-3" class="anchor"></a>

## 32.3 Detail kunjungan

Klik sebuah kunjungan untuk melihat semua yang tercatat di SIMRS: **diagnosa dan prosedur** (ICD-10 / ICD-9), **pemeriksaan SOAP** dokter dan perawat beserta tanda vital, **tindakan**, **obat dan BMHP** yang diberikan beserta aturan pakai, **hasil laboratorium** dengan nilai rujukan (angka merah = di luar normal), **radiologi** dan hasil bacaannya, **rawat inap** (ruang, tanggal masuk dan keluar, status pulang), **laporan operasi**, dan **resume medis**. Pakai tombol bagian di atas untuk melompat; bagian yang pudar berarti kosong.

Tombol **Cetak** mencetak halaman tanpa menu samping. Simpan hasil cetak dengan aman.

<div class="callout catatan"><b>Bila muncul pesan "tidak bisa dibaca dari database Khanza"</b><p>Artinya satu bagian (misalnya radiologi atau resume) memakai tabel yang berbeda di versi Khanza rumah sakit. Bagian lain tetap tampil. Klik pesan itu untuk melihat detailnya dan teruskan ke tim IT.</p></div>
