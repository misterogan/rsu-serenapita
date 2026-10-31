<a id="stock" class="anchor"></a>

# Bab 31. Stok obat farmasi

<p class="lead">Menu Farmasi menggantikan file Excel <i>Persediaan Obat Unit Farmasi</i> dan <i>Persediaan Obat Unit Gudang Farmasi</i>. Petugas cukup mengetik jumlah obat yang masuk dan keluar setiap hari; stok akhir, rata-rata pemakaian, safety stock, titik pesan ulang (ROP), dan nilai stok dihitung otomatis.</p>

Peran **Farmasi** hanya bisa membuka tiga menu: **Input stok harian**, **Rekap persediaan**, dan **Data obat**. Akun awal: `farmasi@rsuserenapita.id` dengan kata sandi `Farmasi@Serenapita26`, yang wajib diganti saat pertama kali masuk. Akun petugas lain dibuat Administrator di menu **Pengguna** dengan peran **Farmasi**, satu akun untuk setiap petugas supaya nama pengisi tercatat.

<div class="callout tips"><b>Tur panduan di setiap halaman</b><p>Klik tombol <b>Panduan</b> di kanan atas halaman Farmasi. Layar akan meredup dan setiap tombol atau kolom disorot satu per satu dengan penjelasannya. Klik <b>Lanjut</b> (atau tombol panah kanan) untuk pindah ke bagian berikutnya, <b>Kembali</b> untuk mundur, dan <b>Lewati</b> atau <kbd>Esc</kbd> untuk menutup. Tur muncul otomatis sekali sehari, yaitu saat pertama kali membuka halaman Farmasi hari itu. Selebihnya, buka tur kapan saja lewat tombol <b>Panduan</b>. Tekan <kbd>Ctrl</kbd> sambil klik <b>Panduan</b> untuk membuka panduan tertulis ini.</p></div>

<a id="stock-1" class="anchor"></a>

## 31.1 Mulai pertama kali: stock opname

Sebelum mengisi transaksi harian, samakan dulu stok sistem dengan stok fisik. Langkah ini cukup dilakukan sekali di awal, lalu diulang setiap jadwal stock opname (misalnya akhir bulan).

1. Buka **Input stok harian**, pilih unit **Gudang Farmasi** atau **Farmasi Sentral**.
2. Pastikan tanggal adalah hari dilakukannya penghitungan.
3. Klik **Stock opname** di sebelah kanan.
4. Hitung fisik obat di rak **sebelum ada barang masuk atau keluar hari itu**.
5. Ketik jumlahnya di kolom **Hasil hitung**, dalam satuan terkecil (tablet, ampul, vial, botol). Kolom **Selisih** langsung menunjukkan beda dengan stok sistem.
6. Perbarui **Tanggal ED** sesuai kemasan dengan kedaluwarsa terdekat.
7. Klik **Simpan** di bagian bawah. Boleh disimpan bertahap, misalnya per rak atau per golongan.
8. Ulangi untuk unit yang lain.

Hasil hitung menjadi stok awal hari itu. Masuk dan keluar pada hari yang sama tetap dihitung, jadi transaksi hari itu bisa langsung diisi setelah stock opname.

<a id="stock-2" class="anchor"></a>

## 31.2 Mengisi masuk dan keluar setiap hari

1. Buka **Input stok harian**. Unit dan tanggal hari ini sudah terpilih.
2. Pastikan pilihan di kanan adalah **Masuk & keluar**.
3. Cari obat: ketik sebagian nama di kotak cari (tekan <kbd>/</kbd> untuk langsung ke kotak cari), atau pilih golongan.
4. Ketik jumlah di kolom **Masuk** dan/atau **Keluar**. Kolom kosong berarti 0. Tekan <kbd>Enter</kbd> untuk turun ke baris berikutnya, <kbd>Shift</kbd>+<kbd>Enter</kbd> untuk naik.
5. Periksa kolom **Sisa** yang dihitung langsung.
6. Isi **Catatan** bila perlu, misalnya *dari PBF*, nomor faktur, *ke farmasi*, *ranap*, atau *IGD*.
7. Klik **Simpan** di bagian bawah.

| Unit | Kolom Masuk | Kolom Keluar |
|---|---|---|
| Gudang Farmasi | Barang yang diterima dari PBF atau distributor | Barang yang dikeluarkan ke Farmasi Sentral atau ruangan |
| Farmasi Sentral | Barang yang diterima dari gudang | Obat yang diberikan ke pasien rawat jalan, rawat inap, IGD, atau ruangan |

| Tanda | Arti |
|---|---|
| Garis biru di kiri baris | Baris sudah diubah tetapi belum disimpan |
| Sisa berwarna oranye | Stok menipis, di bawah titik pesan ulang (ROP). Saatnya memesan |
| Sisa berwarna merah | Stok habis atau minus. Periksa isian |
| Tulisan biru *dari gudang …* | Hanya di Farmasi Sentral: jumlah yang dikeluarkan gudang hari itu. Klik untuk mengisi kolom Masuk |
| ED berwarna oranye / merah | ED kurang dari 90 hari / sudah kedaluwarsa |

**Hal yang perlu diperhatikan**

- Hanya baris yang diubah yang disimpan, jadi beberapa petugas bisa mengisi tanggal yang sama untuk obat berbeda.
- Jumlah keluar tidak bisa melebihi stok. Bila muncul pesan *Jumlah keluar melebihi stok*, periksa angkanya. Bila stok fisik memang berbeda, lakukan stock opname untuk obat itu dulu.
- Isian yang terlewat: klik panah kiri di samping tanggal untuk membuka hari sebelumnya, lalu isi seperti biasa.
- Koreksi: buka tanggal yang sama lalu ubah angkanya. Kosongkan semua kolom pada baris itu lalu simpan untuk menghapus isian.
- Bila Anda pindah unit, tanggal, atau halaman sebelum menyimpan, sistem akan bertanya dulu supaya angka tidak hilang.

<a id="stock-paralel" class="anchor"></a>

## 31.3 Pengisian paralel dengan SIMRS

Selama masa peralihan, transaksi obat dicatat di **SIMRS dan di menu Farmasi ini**, untuk memastikan data keduanya sama.

1. Isi transaksi di SIMRS seperti biasa, lalu isi angka yang sama di **Input stok harian** pada hari yang sama.
2. Setiap akhir hari atau minggu, buka **Rekap persediaan**, pilih unit dan bulan, lalu klik **Unduh Excel (CSV)**.
3. Bandingkan kolom **Stok akhir** dengan laporan stok di SIMRS untuk unit yang sama.
4. Bila ada selisih, buka **kartu stok** obat tersebut (klik namanya di rekap) dan cocokkan per tanggal dengan riwayat di SIMRS untuk menemukan transaksi yang terlewat atau salah ketik.
5. Perbaiki di tempat yang salah. Bila keduanya ternyata berbeda dengan fisik, lakukan stock opname.

<a id="stock-3" class="anchor"></a>

## 31.4 Rekap persediaan dan kartu stok

**Rekap persediaan** menampilkan per bulan dan per unit:

| Kolom | Keterangan |
|---|---|
| Stok awal | Stok di akhir bulan sebelumnya |
| Masuk / Keluar | Total sebulan |
| Selisih SO | Hasil hitung fisik dikurangi stok sistem saat stock opname |
| Stok akhir | Stok awal + masuk − keluar + selisih SO |
| Pakai/hari | Total keluar dibagi jumlah hari |
| Safety | Pakai/hari × 7 hari |
| ROP | Pakai/hari × 10 hari. Bila stok akhir ≤ ROP, status menjadi **Perlu pesan** |
| Nilai stok | Stok akhir × harga satuan |

- Klik kartu **Perlu dipesan**, **Habis / minus**, atau **ED ≤ 90 hari** di bagian atas untuk menyaring daftar.
- Klik **Unduh Excel (CSV)** untuk membuka rekap di Excel.
- Klik nama obat untuk membuka **kartu stok**: riwayat harian obat itu lengkap dengan nama petugas dan jam pengisian. Klik tanggal di kartu stok untuk membuka lembar isian hari itu.

<a id="stock-4" class="anchor"></a>

## 31.5 Data obat

1. Buka **Data obat**, klik **+ Tambah obat**.
2. Isi **Nama obat** lengkap dengan kekuatan dan bentuk sediaan dan **Kode obat di SIMRS** (sama persis dengan SIMRS), lalu pilih **Golongan** dan **Satuan terkecil**.
3. Isi **Harga** per satuan terkecil termasuk PPN dan **Tanggal ED** terdekat bila sudah tahu.
4. Klik **Simpan**. Obat langsung muncul di lembar isian kedua unit dengan stok 0; isi stok awalnya lewat stock opname atau kolom Masuk.

Daftar obat diambil dari SIMRS beserta kodenya, dengan stok awal dari stock opname 30 Oktober 2026. Obat yang stoknya 0 saat itu dibuat **nonaktif**; aktifkan tombol **Aktif** bila obat tersebut mulai ada stoknya. Golongan obat yang tidak ada di daftar Excel lama diperkirakan dari nama dan satuannya, jadi periksa dan rapikan bila perlu.

Obat yang sudah punya riwayat stok tidak bisa dihapus. Matikan tombol **Aktif** agar tidak muncul lagi di lembar isian; riwayatnya tetap tersimpan.
