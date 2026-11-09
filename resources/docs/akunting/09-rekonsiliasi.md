<a id="cl-rekonsiliasi" class="anchor"></a>

# Bab 9. Rekonsiliasi

<p class="lead">Mencocokkan saldo buku besar dengan rincian transaksi Khanza per cut off. Rincian ini sekaligus menjadi saldo awal buku pembantu di SIMRS baru: piutang per pasien dan hutang per faktur.</p>

## 9.1 Cara membaca tabel rekonsiliasi

Setiap bagian (piutang, hutang, persediaan) punya tabel per akun COA:

| Kolom | Arti |
|---|---|
| Buku besar | Saldo akun COA setelah pemetaan dan penyesuaian, nilai normal. |
| Rincian Khanza | Jumlah rincian transaksi yang termasuk akun itu. |
| Selisih | Buku besar − rincian. **cocok** bila kurang dari Rp 1. |
| Buat penyesuaian | Membuka formulir jurnal penyesuaian yang sudah terisi sebagian (9.6). |

Tekan **Lihat rincian** untuk melihat 200 baris terbesar. Rincian lengkap ada di **Unduh rincian (Excel)**.

## 9.2 Piutang pasien

Rincian berisi piutang pasien di Khanza yang:

- tanggal piutangnya pada atau sebelum cut off, dan
- **masih tersisa per cut off**. Sisa = nilai piutang − uang muka − pembayaran, diskon, dan bagian tidak terbayar yang dicatat **pada atau sebelum** cut off.

Pelunasan yang terjadi setelah cut off **tidak** mengurangi, karena per cut off piutang itu memang masih terbuka. Pelunasan setelah cut off dicatat di SIMRS baru.

Setiap piutang dimasukkan ke akun menurut nama penjamin:

| Penjamin mengandung kata | Akun |
|---|---|
| BPJS | 1111 Piutang Usaha – BPJS |
| UMUM atau PASIEN | 1110 Piutang Usaha – Pasien Umum |
| Lainnya (asuransi, perusahaan) | 1112 Piutang Usaha – Asuransi Swasta |

Penyebab selisih yang umum:

- Piutang dibuat di Khanza tanpa jurnal, atau jurnalnya ke rekening lain.
- Pelunasan dicatat di jurnal tetapi tidak di menu bayar piutang, atau sebaliknya.
- Rekening piutang dipetakan ke akun COA yang tidak sesuai penjaminnya.
- Selisih klaim BPJS (tarif INA-CBG ≠ tagihan rumah sakit) belum dijurnal.

## 9.3 Hutang supplier

Rincian berisi faktur pemesanan yang diterima pada atau sebelum cut off dan masih tersisa per cut off (tagihan − pembayaran pada atau sebelum cut off).

| Jenis | Akun |
|---|---|
| Medis (obat dan BHP, menu pemesanan farmasi Khanza) | 2101 Hutang Usaha – Supplier Obat |
| Non medis (menu pemesanan non medis Khanza) | 2103 Hutang Usaha – Jasa Pihak Ketiga |

Bila hutang alkes perlu dipisahkan ke 2102, buat jurnal penyesuaian reklasifikasi dari 2101 ke 2102 berdasarkan rincian Excel.

Penyebab selisih yang umum: pembayaran supplier dicatat di jurnal atau pengeluaran harian tetapi tidak di menu bayar pemesanan; retur belum dijurnal; faktur dihapus atau diubah setelah dijurnal.

## 9.4 Persediaan farmasi

Nilai persediaan dihitung dari **stok terakhir** setiap barang di setiap depo (dan setiap batch bila Khanza memakai batch) pada atau sebelum cut off, dikali **harga beli** di data barang Khanza.

| Jenis barang mengandung kata | Akun |
|---|---|
| OBAT | 1120 Persediaan Obat |
| Lainnya | 1121 Persediaan Alat Medis Habis Pakai |

<div class="callout"><b>Nilai persediaan adalah perkiraan</b><p>Harga beli yang dipakai adalah harga di data barang saat data diambil, bukan harga pada setiap pembelian. Karena itu selisih persediaan hanya ditandai sebagai informasi. Sebelum membuat penyesuaian, bandingkan juga dengan hasil stock opname fisik di sekitar tanggal cut off.</p></div>

## 9.5 Jurnal Khanza tidak seimbang

Daftar jurnal Khanza dengan total debet ≠ kredit (selisih Rp 1 atau lebih), dari awal tahun sampai cut off. Setiap jurnal seperti ini membuat neraca saldo tidak seimbang.

Cara memperbaiki:

1. Tekan **Lengkapi** pada baris jurnal.
2. Formulir jurnal **koreksi** terbuka dengan tanggal, keterangan, dan nilai selisih sudah terisi di sisi yang kurang.
3. Isi **Akun COA** tempat seharusnya sisi yang kurang itu dicatat. Untuk menentukannya, lihat no. bukti dan keterangan jurnal, lalu periksa transaksinya di Khanza.
4. Simpan.

Bila jumlahnya banyak, unduh daftarnya (Excel), kelompokkan menurut jenis transaksi, lalu buat satu jurnal koreksi per kelompok.

## 9.6 Membuat penyesuaian dari selisih rekonsiliasi

Tombol **Buat penyesuaian** muncul pada akun yang selisihnya Rp 1 atau lebih. Formulir terbuka dengan dua baris:

- **Baris 1** sudah berisi akun yang selisih dengan nilai selisihnya, di sisi yang membuat buku besar sama dengan rincian. Contoh: buku besar piutang lebih besar dari rincian, maka akun piutang dikredit.
- **Baris 2** berisi nilai yang sama di sisi berlawanan, dengan akun **kosong**. Isi akun lawannya sesuai penyebab selisih. Contoh: pendapatan, kas, atau kerugian piutang.

<div class="callout"><b>Jangan langsung menyesuaikan</b><p>Cari dulu penyebab selisih. Penyesuaian tanpa penjelasan hanya memindahkan masalah ke akun lain. Tulis penyebabnya di keterangan jurnal supaya bisa ditelusuri auditor.</p></div>

## 9.7 Pasien yang melewati cut off

Bagian ini berisi:

- **Dirawat saat cut off**: jumlah pasien ranap lanjutan. Rinciannya ada di halaman Ranap lanjutan (Bab 10).
- **Belum ditagih**: kunjungan sampai cut off yang **per tanggal cut off** belum punya nota dan belum menjadi piutang. Nota atau piutang yang dibuat setelah cut off dianggap belum ada. Kunjungan berstatus *Belum* pada kolom Periksa umumnya pasien yang tidak jadi diperiksa.

Untuk setiap kunjungan yang belum ditagih, tentukan salah satu:

1. Pendapatannya diakui di periode Khanza: buat jurnal penyesuaian (Bab 11.4, contoh C), **atau**
2. Ditagih dan dicatat seluruhnya di SIMRS baru.

Jangan keduanya, supaya pendapatan tidak tercatat dua kali.
