<a id="cl-pengenalan" class="anchor"></a>

# Bab 1. Pengenalan closing akuntansi

<p class="lead">Menu <b>Closing akuntansi</b> dipakai untuk merapikan data keuangan dari SIMRS Khanza sampai tanggal cut off, memindahkannya ke COA (bagan akun) SIMRS baru, lalu menghasilkan saldo awal dan laporan yang dibutuhkan untuk tutup buku tahun berjalan.</p>

## 1.1 Mengapa modul ini dibutuhkan

Rumah sakit berpindah dari SIMRS Khanza ke SIMRS baru di tengah tahun buku. Akibatnya data satu tahun terbagi dua:

| Periode | Sumber data |
|---|---|
| 1 Januari s.d. 1 November 2026 | SIMRS Khanza |
| 2 November 2026 (go-live) s.d. 31 Desember 2026 | SIMRS baru |
| Setelah 1 November, khusus pasien rawat inap yang sudah dirawat sebelum go-live | Tetap di Khanza sampai pasien pulang (disebut **ranap lanjutan**) |

Untuk closing di bulan Januari, Akunting membutuhkan data Khanza yang sudah rapi dan memakai COA yang sama dengan SIMRS baru. Modul ini mengerjakan hal itu tanpa mengubah satu pun data di Khanza.

## 1.2 Apa yang dikerjakan modul

1. **Mengambil data dari Khanza** dan menyimpan salinannya di website: buku besar (rekening dan jurnal), piutang pasien, hutang supplier, persediaan obat, serta data pasien. Setelah salinan diambil, closing tetap bisa dikerjakan walaupun server Khanza sudah dimatikan.
2. **Memetakan** setiap rekening Khanza ke satu akun COA baru.
3. **Menyusun** neraca saldo, neraca, laba rugi, dan laba rugi per bulan dengan COA baru.
4. **Mencocokkan** (rekonsiliasi) saldo buku besar dengan rincian piutang, hutang, dan persediaan.
5. **Mencatat jurnal penyesuaian dan koreksi** dari Akunting, tanpa mengubah data Khanza.
6. **Memisahkan transaksi ranap lanjutan** setelah cut off supaya tidak masuk saldo awal dan tidak tercatat dua kali.
7. **Menghasilkan file Excel**: saldo awal untuk SIMRS baru beserta semua rinciannya.
8. **Mengunci** angka setelah selesai.

## 1.3 Istilah yang dipakai

| Istilah | Arti |
|---|---|
| Cut off | Hari terakhir Khanza dipakai penuh. Bawaan: **1 November 2026**. |
| Saldo awal SIMRS baru | Saldo setiap akun per hari setelah cut off (**2 November 2026**), untuk diunggah ke SIMRS baru. |
| Awal tahun buku | Tanggal mulai periode yang ditutup. Bawaan: **1 Januari 2026**. |
| Rekening Khanza | Akun di tabel `rekening` Khanza, misalnya `111010 Kas`. |
| COA baru | Bagan akun SIMRS baru, misalnya `1101 Kas Kecil`. Hanya akun **Child/Detail** yang dipakai untuk transaksi. |
| Pemetaan | Pasangan rekening Khanza → akun COA baru. |
| Data Khanza (salinan) | Hasil "Ambil data dari Khanza" yang disimpan di website. |
| Ranap lanjutan | Pasien yang dirawat saat cut off dan tetap dilayani di Khanza sampai pulang. |
| D−K | Saldo bertanda: debet dikurangi kredit. Positif = saldo debet, negatif = saldo kredit. |
| Nilai normal | Saldo menurut sisi normal akun. Aset dan beban: debet positif. Kewajiban, ekuitas, dan pendapatan: kredit positif. |

## 1.4 Siapa bisa melakukan apa

| Peran | Hak |
|---|---|
| **Akunting** | Melihat dan mengubah semua bagian: pengaturan, ambil data, COA, pemetaan, jurnal penyesuaian, kunci closing, unduh Excel. |
| **Komisaris** | Hanya melihat dan mengunduh Excel. Tombol ubah tidak tampil, dan di bagian atas halaman ada tanda **Hanya lihat**. |
| Direktur dan peran lain | Tidak bisa membuka menu ini. |

<div class="callout"><b>Data Khanza tidak pernah diubah</b><p>Modul hanya membaca data Khanza. Semua pemetaan, jurnal penyesuaian, dan kunci disimpan di database website. Jika ada kesalahan, cukup perbaiki di modul ini; Khanza tidak perlu disentuh.</p></div>
