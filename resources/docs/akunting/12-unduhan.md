<a id="cl-unduhan" class="anchor"></a>

# Bab 12. File Excel dan saldo awal SIMRS baru

## 12.1 Paket closing

Tombol **Unduh paket closing (Excel)** di halaman Ringkasan menghasilkan satu file dengan sheet berikut:

| Sheet | Isi |
|---|---|
| Saldo awal SIMRS baru | Saldo per akun COA per 2 November 2026 (laba rugi ditutup ke 3202). |
| Neraca saldo | Seperti halaman Neraca saldo, ditambah daftar rekening Khanza asal setiap akun. |
| Neraca | Neraca berjenjang per cut off. |
| Laba rugi | Laba rugi awal tahun s.d. cut off. |
| Laba rugi per bulan | Per akun per bulan. |
| Pemetaan akun | Setiap rekening Khanza dengan saldo dan akun COA pasangannya. Kolom Catatan menandai rekening belum dipetakan, tidak terdaftar, atau jenisnya berbeda. |
| Jurnal penyesuaian | Semua jurnal penyesuaian dan koreksi, per baris. |
| Rincian piutang | Piutang per pasien per cut off, dengan akun COA-nya. |
| Rincian hutang | Hutang per faktur per cut off, dengan akun COA-nya. |
| Persediaan | Stok dan nilai per barang per depo per cut off. |
| Belum ditagih | Pasien dirawat saat cut off dan kunjungan yang belum ditagih per cut off. |
| Ranap lanjutan per bulan | Ringkasan jurnal Khanza setelah cut off per bulan per akun COA. |
| Ranap lanjutan rinci | Setiap baris jurnal Khanza setelah cut off. |
| Jurnal tidak seimbang | Jurnal Khanza dengan debet ≠ kredit. |
| COA | Daftar COA yang dipakai. |

Setiap sheet mencantumkan periode, waktu file dibuat, dan status closing. Tulisan **closing BELUM dikunci** berarti angka masih bisa berubah. Untuk arsip akhir, unduh paket setelah closing dikunci.

Setiap halaman juga punya tombol unduh untuk sheet yang sesuai saja.

## 12.2 Saldo awal SIMRS baru

Di halaman Ringkasan, kartu **Saldo awal SIMRS baru** punya pilihan **Saldo laba rugi**:

| Pilihan | Isi file | Kapan dipakai |
|---|---|---|
| **Ditutup ke 3202 Laba Tahun Berjalan** (bawaan) | Hanya akun neraca. Seluruh laba rugi Januari s.d. cut off digabung menjadi satu saldo di 3202. | SIMRS baru hanya menerima saldo neraca, atau laba rugi Januari–cut off akan dilaporkan dari modul ini. |
| **Dibawa per akun pendapatan/beban** | Akun neraca dan setiap akun pendapatan/beban dengan saldonya. | SIMRS baru diharapkan langsung menampilkan laba rugi setahun penuh tanpa menggabung manual. |

Kolom file: **Account Number**, **Account Name**, **Parent Account**, **Kategori Akun**, **Debit**, **Kredit**. Total debit selalu sama dengan total kredit bila neraca saldo seimbang. Baris *BELUM DIPETAKAN* hanya muncul bila masih ada rekening yang belum dipetakan; selesaikan dulu sebelum mengunggah.

## 12.3 Mengunggah ke SIMRS baru

Langkah pastinya mengikuti fitur saldo awal di SIMRS baru. Secara umum:

1. Pastikan COA di SIMRS baru sama dengan COA di modul ini (bandingkan dengan sheet COA).
2. Masukkan saldo awal per **2 November 2026** sesuai sheet *Saldo awal SIMRS baru*, sebagai jurnal saldo awal atau lewat fitur impor.
3. Masukkan buku pembantu: piutang per pasien (sheet *Rincian piutang*) dan hutang per faktur (sheet *Rincian hutang*). Pastikan totalnya sama dengan saldo akun piutang dan hutang.
4. Cocokkan neraca saldo SIMRS baru per 2 November dengan sheet *Saldo awal SIMRS baru*.
5. Simpan file yang diunggah sebagai arsip.
