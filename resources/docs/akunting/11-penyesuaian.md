<a id="cl-penyesuaian" class="anchor"></a>

# Bab 11. Jurnal penyesuaian dan koreksi

<p class="lead">Jurnal yang dibuat Akunting untuk merapikan saldo Khanza sebelum menjadi saldo awal SIMRS baru. Jurnal ini memakai akun COA baru dan tidak mengubah data di Khanza.</p>

## 11.1 Dua jenis jurnal

| Jenis | Aturan | Dipakai untuk |
|---|---|---|
| **Penyesuaian / reklasifikasi** | Total debet wajib sama dengan total kredit, minimal dua baris. | Koreksi salah akun, selisih rekonsiliasi, penyusutan, akrual, reklasifikasi, saldo awal manual. |
| **Koreksi selisih Khanza** | Boleh sepihak (debet ≠ kredit), minimal satu baris. | Hanya untuk melengkapi jurnal Khanza yang tidak seimbang, atau saldo awal Khanza yang tidak seimbang. |

Jurnal koreksi diberi tanda kuning **Koreksi selisih Khanza** di daftar jurnal. Setelah semua koreksi dibuat, neraca saldo seharusnya seimbang. Gunakan jenis ini dengan hati-hati: koreksi yang salah juga membuat neraca saldo tidak seimbang.

## 11.2 Membuat jurnal

1. Buka tab **Jurnal penyesuaian**, tekan **Buat jurnal**.
2. Pilih **Jenis jurnal**.
3. Isi **Tanggal**. Harus di antara awal tahun buku dan tanggal cut off. Tanggal menentukan bulan jurnal ini di laporan laba rugi per bulan.
4. Isi **Keterangan**: jelaskan alasan dan dasar penyesuaian.
5. Isi baris jurnal:
   - **Akun COA**: ketik kode atau sebagian nama lalu pilih. Nama akun tampil di sebelahnya.
   - **Memo** (opsional): keterangan per baris, misalnya no. jurnal Khanza atau nama pasien.
   - **Debet** atau **Kredit**: isi salah satu saja per baris.
6. Tekan **+ Tambah baris** bila perlu lebih dari dua baris. Ikon tempat sampah menghapus baris (minimal dua baris tetap tersisa).
7. Perhatikan tanda di bawah tabel: **Seimbang** (hijau), **Belum seimbang** (merah), atau **Koreksi sepihak** (kuning, khusus jenis koreksi).
8. Tekan **Simpan jurnal**.

Nomor jurnal dibuat otomatis berurutan: JP-0001, JP-0002, dan seterusnya. Baris yang dibiarkan kosong diabaikan.

Jurnal ditolak bila: akun bukan akun detail COA, satu baris berisi debet dan kredit sekaligus (atau keduanya kosong), tanggal di luar periode, atau jurnal penyesuaian tidak seimbang. Pesan kesalahan menyebut baris yang bermasalah.

## 11.3 Mengubah dan menghapus

Di daftar jurnal, setiap jurnal punya tombol **Ubah** dan **Hapus**. Semua perubahan langsung memengaruhi neraca saldo, laporan, rekonsiliasi, dan saldo awal. Setiap pembuatan, perubahan, dan penghapusan tercatat di log aktivitas. Bila closing dikunci, jurnal tidak bisa diubah atau dihapus.

## 11.4 Contoh jurnal yang umum

**A. Koreksi salah akun.** Pendapatan laboratorium Rp 2.500.000 tercatat di Khanza sebagai pendapatan rawat jalan.

| Akun | Debet | Kredit |
|---|---|---|
| 4102 Pendapatan Rawat Jalan | 2.500.000 | |
| 4105 Pendapatan Laboratorium | | 2.500.000 |

**B. Piutang belum dijurnal di Khanza.** Rekonsiliasi menunjukkan piutang pasien umum di rincian Rp 200.000 lebih besar dari buku besar, karena tagihan rawat jalan tidak terjurnal.

| Akun | Debet | Kredit |
|---|---|---|
| 1110 Piutang Usaha – Pasien Umum | 200.000 | |
| 4102 Pendapatan Rawat Jalan | | 200.000 |

**C. Akrual pendapatan yang belum ditagih per cut off.** Bertanggal 1 November 2026.

| Akun | Debet | Kredit |
|---|---|---|
| 1111 Piutang Usaha – BPJS | 3.000.000 | |
| 4101 Pendapatan Rawat Inap | | 3.000.000 |

**D. Penyusutan yang belum dicatat** (Januari s.d. Oktober), bertanggal sesuai bulan atau satu kali di akhir periode.

| Akun | Debet | Kredit |
|---|---|---|
| 5404 Biaya Penyusutan Peralatan Medis | 15.000.000 | |
| 1209 Akumulasi Penyusutan Peralatan Medis | | 15.000.000 |

**E. Saldo awal 1 Januari dari laporan keuangan 2025** (bila sumber saldo awal "Tanpa saldo awal"). Bertanggal 1 Januari 2026, berisi semua akun neraca per 31 Desember 2025. Selisih ekuitas dimasukkan ke 3201 Saldo Laba Ditahan supaya seimbang.

| Akun | Debet | Kredit |
|---|---|---|
| 1102 Kas di Bank – Operasional | 250.000.000 | |
| 1204 Peralatan Medis | 800.000.000 | |
| 2101 Hutang Usaha – Supplier Obat | | 120.000.000 |
| 3101 Modal Pemilik/Yayasan | | 900.000.000 |
| 3201 Saldo Laba Ditahan | | 30.000.000 |

**F. Melengkapi jurnal Khanza tidak seimbang** (jenis koreksi). Jurnal Khanza mendebet kas Rp 50.000 tetapi hanya mengkredit pendapatan obat Rp 40.000.

| Akun | Debet | Kredit |
|---|---|---|
| 4107 Pendapatan Farmasi | | 10.000 |

**G. Reklasifikasi hutang alkes** dari 2101 ke 2102 sesuai rincian hutang.

| Akun | Debet | Kredit |
|---|---|---|
| 2101 Hutang Usaha – Supplier Obat | 45.000.000 | |
| 2102 Hutang Usaha – Supplier Alat Medis | | 45.000.000 |

Tekan **Unduh jurnal (Excel)** untuk menyimpan semua jurnal penyesuaian sebagai lampiran kertas kerja.
