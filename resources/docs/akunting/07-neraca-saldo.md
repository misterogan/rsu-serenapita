<a id="cl-neraca-saldo" class="anchor"></a>

# Bab 7. Neraca saldo

<p class="lead">Saldo setiap akun COA baru per cut off: dari Khanza, ditambah jurnal penyesuaian, sampai saldo akhir. Halaman ini memastikan total debet sama dengan total kredit.</p>

## 7.1 Membaca kolom

| Kolom | Arti |
|---|---|
| Akun | Kode, nama, dan kategori akun COA. Baris **— Belum dipetakan** berwarna merah berisi saldo rekening Khanza yang belum punya pasangan COA. |
| Saldo awal | Saldo per awal tahun buku, bertanda D−K (minus berarti saldo kredit, tampil merah). |
| Mutasi debet / kredit | Total jurnal Khanza dari awal tahun sampai cut off. |
| Khanza debet / kredit | Saldo akhir dari Khanza sebelum penyesuaian. |
| Penyesuaian D / K | Total jurnal penyesuaian dan koreksi dari Akunting. |
| Akhir debet / kredit | Saldo akhir setelah penyesuaian. Angka inilah yang menjadi saldo awal SIMRS baru. |

Baris **Total** di bagian bawah harus menunjukkan **Akhir debet = Akhir kredit**.

Jurnal Khanza **setelah** cut off (ranap lanjutan) tidak masuk neraca saldo.

## 7.2 Melihat asal saldo

Pilih **Dengan rincian rekening Khanza**. Di bawah setiap akun COA tampil rekening Khanza yang dipetakan ke akun itu beserta saldonya. Gunakan tampilan ini untuk menelusuri saldo yang terlihat aneh, misalnya akun aset yang saldonya kredit.

## 7.3 Bila neraca saldo tidak seimbang

Di atas tabel muncul pesan merah berisi selisihnya. Selisih neraca saldo tidak bisa ditutup dengan jurnal penyesuaian biasa, karena jurnal penyesuaian selalu seimbang. Penyebab dan cara mengatasinya:

| Penyebab | Cara memeriksa | Cara mengatasi |
|---|---|---|
| Rekening belum dipetakan | Ada baris *Belum dipetakan*. | Petakan (Bab 6). Ini tidak menimbulkan selisih total, tetapi membuat neraca tidak lengkap. |
| Jurnal Khanza tidak seimbang | Lihat Rekonsiliasi → Jurnal Khanza tidak seimbang. Pesan merah juga menyebut *sudah ada di data Khanza*. | Tekan **Lengkapi** pada setiap jurnal untuk membuat jurnal koreksi (Bab 9.5). |
| Saldo awal tahun di Khanza tidak seimbang | Total kolom Saldo awal tidak nol. | Coba sumber saldo awal "Akumulasi jurnal" (Bab 4.4), atau perbaiki dengan jurnal koreksi bertanggal 1 Januari. |
| Saldo awal kosong padahal seharusnya ada | Kolom Saldo awal kosong semua. | Ganti sumber saldo awal (Bab 4.4). |

Setelah koreksi dibuat, halaman ini langsung menghitung ulang; data Khanza tidak perlu diambil ulang.

Tekan **Unduh neraca saldo (Excel)** untuk menyimpannya. Versi Excel juga memuat kolom daftar rekening Khanza asal setiap akun.
