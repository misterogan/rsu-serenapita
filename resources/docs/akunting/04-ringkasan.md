<a id="cl-ringkasan" class="anchor"></a>

# Bab 4. Halaman Ringkasan

<p class="lead">Halaman pertama saat membuka Closing akuntansi. Di sini Akunting mengatur tanggal, mengambil data dari Khanza, melihat daftar periksa, mengunduh saldo awal, dan mengunci closing.</p>

## 4.1 Kartu angka di bagian atas

Kartu ini muncul setelah data Khanza diambil. Semua angka sudah memperhitungkan pemetaan dan jurnal penyesuaian.

| Kartu | Arti |
|---|---|
| Total aset | Jumlah saldo akun kategori Aset per cut off. |
| Kewajiban | Jumlah saldo akun kategori Kewajiban. |
| Ekuitas | Jumlah saldo akun Ekuitas, **belum** termasuk laba tahun berjalan. |
| Laba (rugi) berjalan | Pendapatan dikurangi beban dari awal tahun sampai cut off. |
| Selisih neraca | Aset − (Kewajiban + Ekuitas + Laba berjalan). Hijau bila 0, merah bila ada selisih. |
| Belum dipetakan | Hanya muncul bila ada saldo rekening Khanza yang belum punya pasangan COA. |

## 4.2 Daftar periksa closing

Daftar ini menunjukkan apa yang sudah beres dan apa yang masih harus dikerjakan. Tombol **Buka** membawa ke halaman yang perlu diperbaiki.

| Tanda | Arti |
|---|---|
| ✓ hijau | Sudah beres. |
| ! kuning | Perlu diperiksa, tetapi tidak menghalangi closing. |
| ✕ merah | Harus diselesaikan sebelum closing bisa dikunci. |
| i biru | Informasi yang perlu diputuskan Akunting. |

Isi daftar periksa:

| Pemeriksaan | Bila belum beres |
|---|---|
| COA baru dimuat | Unggah template COA (Bab 5). |
| Data Khanza diambil | Tekan **Ambil data dari Khanza**. Merah juga muncul bila pengaturan berubah sejak data diambil; ambil ulang data. |
| Tidak ada pendaftaran baru di Khanza setelah cut off | Ada pasien baru yang masih didaftarkan di Khanza setelah go-live. Koordinasikan dengan pendaftaran; transaksi pasien itu harus dicatat di SIMRS baru. |
| Ranap lanjutan di Khanza | Informasi jumlah pasien ranap lanjutan dan berapa yang belum pulang (Bab 10). |
| Jurnal Khanza lain setelah cut off | Ada jurnal Khanza setelah cut off yang tidak terkait pasien ranap lanjutan (Bab 10). |
| Rekening ranap lanjutan dipetakan | Petakan rekening yang hanya dipakai jurnal setelah cut off (Bab 6). |
| Jurnal Khanza seimbang | Ada jurnal Khanza yang debet ≠ kredit. Lengkapi dengan jurnal koreksi (Bab 9.5). |
| Semua rekening Khanza dipetakan | Petakan rekening yang tersisa (Bab 6). |
| Jenis rekening sesuai | Rekening laba rugi Khanza dipetakan ke akun neraca, atau sebaliknya. Periksa pemetaannya (Bab 6.5). |
| Neraca saldo seimbang | Lihat Bab 7.3. |
| Rekonsiliasi piutang / hutang / persediaan | Lihat Bab 9. Untuk persediaan, selisih hanya ditandai sebagai informasi karena nilainya perkiraan. |
| Pasien belum ditagih per cut off | Kunjungan sebelum cut off yang belum ditagih (Bab 9.4). |
| Kunci closing | Kunci setelah semua beres (Bab 13). |

## 4.3 Pengaturan

| Isian | Keterangan |
|---|---|
| Awal tahun buku | Tanggal mulai periode yang ditutup. Untuk tahun 2026: `01/01/2026`. |
| Tanggal cut off | Hari terakhir Khanza dipakai penuh: `01/11/2026`. Saldo awal SIMRS baru dihitung per hari berikutnya, 2 November 2026. |
| Sumber saldo awal tahun | Dari mana saldo per 1 Januari diambil. Lihat 4.4. |

Tekan **Simpan pengaturan**. Setiap kali pengaturan diubah, **ambil ulang data dari Khanza** agar angka sesuai. Rentang awal tahun buku sampai cut off paling lama satu tahun.

## 4.4 Memilih sumber saldo awal tahun

Neraca per cut off = saldo per 1 Januari + semua jurnal 1 Januari s.d. cut off. Saldo per 1 Januari bisa diambil dengan tiga cara:

| Pilihan | Pakai bila | Catatan |
|---|---|---|
| **Saldo awal tahun di Khanza (tabel rekeningtahun)** | Saldo awal 2026 sudah diisi di Khanza, atau buku 2025 sudah ditutup di Khanza. | Pilihan bawaan. |
| **Akumulasi semua jurnal Khanza sebelum awal tahun** | Saldo awal 2026 tidak pernah diisi, tetapi Khanza sudah menjurnal transaksi sejak tahun-tahun sebelumnya. | Laba rugi tahun-tahun sebelumnya muncul sebagai satu rekening bernama *Akumulasi laba/rugi sebelum 1 Jan 2026* dengan kode `#LABA-LALU`. Petakan ke **3201 Saldo Laba Ditahan**. |
| **Tanpa saldo awal** | Saldo 1 Januari ada di luar Khanza, misalnya dari laporan keuangan audit 2025. | Masukkan saldo 1 Januari sebagai jurnal penyesuaian bertanggal 1 Januari 2026 (Bab 11.4, contoh E). |

<div class="callout tips"><b>Cara cepat memilih</b><p>Mulai dengan pilihan bawaan, ambil data, lalu buka Neraca saldo. Bila kolom saldo awal kosong semua atau neraca saldo tidak seimbang dengan selisih besar, ganti ke "Akumulasi semua jurnal", ambil ulang data, dan bandingkan hasilnya. Bila keduanya tidak cocok dengan laporan keuangan 2025, pakai "Tanpa saldo awal".</p></div>

## 4.5 Mengambil data dari Khanza

1. Tekan **Ambil data dari Khanza** (atau **Ambil ulang data dari Khanza** bila sudah pernah).
2. Tunggu. Proses bisa memakan waktu beberapa menit, tergantung banyaknya transaksi. Jangan menutup halaman.
3. Setelah selesai muncul pesan hijau berisi jumlah rekening dan baris mutasi yang terbaca.

Data yang diambil:

| Data | Isi |
|---|---|
| Buku besar | Daftar rekening, saldo awal tahun, dan mutasi debet/kredit per rekening per bulan. |
| Jurnal tidak seimbang | Jurnal dengan total debet ≠ kredit. |
| Piutang | Piutang pasien yang belum lunas per cut off. |
| Hutang | Faktur supplier (obat/BHP dan non medis) yang belum lunas per cut off. |
| Persediaan | Stok obat/BHP per depo per cut off. |
| Pasien | Pasien belum ditagih per cut off dan pasien yang dirawat saat cut off. |
| Ranap lanjutan | Semua jurnal Khanza setelah cut off. |

Website menyimpan tiga salinan terakhir. Pemetaan dan jurnal penyesuaian tidak berubah saat data diambil ulang.

<div class="callout"><b>Bila muncul pesan "bagian tidak bisa dibaca"</b><p>Struktur sebagian tabel Khanza bisa berbeda antar versi. Hanya bagian itu yang gagal; bagian lain tetap dipakai. Buka tulisan merah "… bagian tidak bisa dibaca dari Khanza" di kartu Data Khanza dan kirimkan isinya ke tim IT. Contoh: bila tabel hutang non medis tidak ada di Khanza, rincian hutang hanya berisi hutang obat/BHP.</p></div>

Bila muncul pesan *Koneksi Khanza belum diatur* atau *Tidak bisa terhubung ke database Khanza*, hubungi tim IT.

## 4.6 Kartu Saldo awal SIMRS baru

Pilih cara saldo laba rugi dibawa, lalu tekan **Unduh (Excel)**. Penjelasan lengkap ada di Bab 12.2.

## 4.7 Unduh paket closing

Tombol **Unduh paket closing (Excel)** di kanan atas menghasilkan satu file berisi semua laporan dan rincian dalam sheet terpisah (Bab 12).
