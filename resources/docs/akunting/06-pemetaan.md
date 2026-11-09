<a id="cl-pemetaan" class="anchor"></a>

# Bab 6. Pemetaan akun

<p class="lead">Menghubungkan setiap rekening Khanza ke satu akun detail COA baru. Pemetaan menentukan ke mana saldo setiap rekening Khanza dipindahkan.</p>

## 6.1 Aturan pemetaan

- Setiap rekening Khanza yang **punya saldo atau mutasi** wajib dipetakan, termasuk rekening yang hanya dipakai jurnal ranap lanjutan setelah cut off.
- Satu rekening Khanza hanya ke **satu** akun COA.
- **Beberapa** rekening Khanza boleh ke akun COA yang sama. Contoh: `Pendapatan Obat Ralan` dan `Pendapatan Obat Ranap` sama-sama ke `4107 Pendapatan Farmasi`.
- Hanya akun **Child/Detail** yang bisa dipilih.
- Rekening Khanza yang saldonya nol dan tidak pernah dipakai tidak perlu dipetakan.

## 6.2 Membaca tabel pemetaan

| Kolom | Arti |
|---|---|
| Rekening Khanza | Kode dan nama rekening. Tanda merah **tidak terdaftar** berarti kode dipakai di jurnal tetapi tidak ada di tabel rekening Khanza. Tetap petakan berdasarkan isi jurnalnya. |
| Tipe | Tipe dan sisi normal di Khanza. `N` neraca, `M` modal, `R` rugi/laba; `D` debet, `K` kredit. Contoh `R/K` = rekening pendapatan. |
| Saldo awal | Saldo per awal tahun buku (D−K). |
| Mutasi D / Mutasi K | Total debet dan kredit dari awal tahun sampai cut off. |
| Saldo cut off | Saldo akhir dengan tanda D atau K. |
| Akun COA baru | Kotak isian kode COA. Nama akun tampil di bawahnya. |
| Saran | Kode COA usulan sistem berdasarkan nama rekening. Tekan tombolnya untuk memakai saran. |

## 6.3 Menyaring daftar

| Pilihan | Isi |
|---|---|
| Bersaldo/bermutasi | Semua rekening yang wajib dipetakan (bawaan). |
| Belum dipetakan | Rekening wajib yang kotak COA-nya masih kosong. |
| Perlu diperiksa | Rekening dengan tanda *jenis berbeda* atau *kode tidak ada di COA*. |
| Semua rekening | Seluruh rekening Khanza, termasuk yang tidak bersaldo. |

Kotak pencarian menerima kode atau nama rekening Khanza, atau kode COA.

## 6.4 Cara memetakan

**Cara cepat untuk pertama kali:**

1. Tekan **Terapkan … saran untuk rekening yang belum dipetakan**. Semua rekening yang belum dipetakan dan punya saran langsung terisi.
2. Pilih filter **Bersaldo/bermutasi** dan periksa baris demi baris. Saran dibuat dari kata pada nama rekening, jadi **wajib diperiksa**.
3. Pilih **Belum dipetakan** untuk mengisi rekening yang tidak mendapat saran.

**Mengisi atau mengubah satu per satu:**

1. Klik kotak **Akun COA baru** pada baris rekening.
2. Ketik kode COA, atau ketik sebagian nama akun lalu pilih dari daftar yang muncul.
3. Periksa nama akun di bawah kotak. Bila tertulis *Kode tidak ada di COA*, kode salah.
4. Kotak yang diubah berwarna kuning, dan jumlah perubahan tampil di bagian bawah.
5. Tekan **Simpan pemetaan** di bagian bawah.

Untuk menghapus pemetaan, kosongkan kotaknya lalu simpan. Bila ada kode yang bukan akun detail COA, baris itu tidak disimpan dan muncul pesan merah berisi daftarnya; baris lain tetap tersimpan.

<div class="callout"><b>Jangan pindah halaman sebelum menyimpan</b><p>Perubahan yang belum disimpan hilang bila halaman ditinggalkan. Browser akan memberi peringatan bila masih ada perubahan.</p></div>

## 6.5 Tanda "jenis berbeda"

Tanda kuning ini muncul bila rekening **laba rugi** Khanza (tipe R) dipetakan ke akun **neraca** COA (Aset, Kewajiban, Ekuitas), atau rekening **neraca** Khanza (tipe N/M) dipetakan ke akun **laba rugi** (Pendapatan, Beban, Lain-lain). Biasanya ini salah pilih. Contoh: `Pendapatan Obat` dipetakan ke `1120 Persediaan Obat`.

Bila memang disengaja, misalnya rekening Khanza yang tipenya salah diatur sejak awal, tanda ini boleh diabaikan.

## 6.6 Contoh pemetaan umum

| Rekening Khanza (contoh nama) | Akun COA baru |
|---|---|
| Kas, Kas Kasir, Kas Ralan, Kas Ranap | 1101 Kas Kecil |
| Bank …, Giro … | 1102 Kas di Bank – Operasional |
| Bank BPJS | 1103 Kas di Bank – BPJS |
| Piutang Pasien, Piutang Umum | 1110 Piutang Usaha – Pasien Umum |
| Piutang BPJS | 1111 Piutang Usaha – BPJS |
| Piutang Asuransi / Perusahaan | 1112 Piutang Usaha – Asuransi Swasta |
| Persediaan Obat | 1120 Persediaan Obat |
| Persediaan BHP / Alkes | 1121 Persediaan Alat Medis Habis Pakai |
| Hutang Obat / Hutang Farmasi | 2101 Hutang Usaha – Supplier Obat |
| Hutang Alkes / BHP | 2102 Hutang Usaha – Supplier Alat Medis |
| Hutang Jasa Medis Dokter | 2111 Hutang Dokter (Jasa Medis Belum Dibayar) |
| Titipan / Deposit Pasien | 2130 Pendapatan Diterima Dimuka (Uang Muka Pasien) |
| Modal | 3101 Modal Pemilik/Yayasan |
| `#LABA-LALU` Akumulasi laba/rugi sebelum 1 Jan | 3201 Saldo Laba Ditahan |
| Pendapatan Kamar / Tindakan Ranap | 4101 Pendapatan Rawat Inap |
| Pendapatan Tindakan Ralan / Poli | 4102 Pendapatan Rawat Jalan |
| Pendapatan IGD | 4103 Pendapatan IGD |
| Pendapatan Operasi | 4104 Pendapatan Tindakan Medis |
| Pendapatan Laboratorium | 4105 Pendapatan Laboratorium |
| Pendapatan Radiologi | 4106 Pendapatan Radiologi |
| Pendapatan Obat Ralan / Ranap | 4107 Pendapatan Farmasi |
| Pendapatan Registrasi / Administrasi | 4109 Pendapatan Administrasi Pasien |
| HPP Obat Ralan | 5102 Biaya Obat Rawat Jalan |
| HPP Obat Ranap | 5101 Biaya Obat Rawat Inap |
| Beban Jasa Medis Dokter | 5106 Biaya Jasa Dokter |
| Beban Jasa Perawat / Paramedis | 5107 Biaya Jasa Tenaga Medis Lainnya |
| Beban Gaji | 5301 atau 5302 (sesuai jenis pegawai) |
| Beban Listrik / Air | 5205 Biaya Utilitas |
| Penghapusan Piutang / Piutang Tidak Terbayar | 6102 Kerugian Piutang Tak Tertagih |

Rekening Khanza untuk **potongan atau diskon** pendapatan tidak punya pasangan khusus di COA. Pilihan yang umum: petakan ke akun pendapatan yang sama (sebagai pengurang), atau ke `6201 Pendapatan/Beban Lainnya`. Tetapkan satu kebijakan dan pakai secara konsisten.

Tekan **Unduh pemetaan (Excel)** untuk menyimpan daftar pemetaan, misalnya untuk diperiksa atasan atau auditor.
