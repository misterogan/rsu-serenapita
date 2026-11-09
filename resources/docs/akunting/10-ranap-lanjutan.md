<a id="cl-ranap" class="anchor"></a>

# Bab 10. Ranap lanjutan

<p class="lead">Pasien yang masih dirawat pada akhir tanggal cut off tetap dilayani di Khanza sampai pulang. Halaman ini memisahkan transaksi mereka setelah cut off supaya bisa dicatat di SIMRS baru tanpa tercatat dua kali.</p>

## 10.1 Mengapa dipisahkan

Saldo awal SIMRS baru dihitung per 2 November 2026. Transaksi ranap lanjutan terjadi **setelah** tanggal itu, jadi tidak boleh masuk saldo awal. Transaksi itu juga tidak ada di SIMRS baru, karena pasiennya dilayani di Khanza. Bila tidak dicatat, pendapatan November dan Desember akan kurang.

Jalan keluarnya: modul meringkas jurnal Khanza setelah cut off **per bulan per akun COA**, lalu Akunting mencatat ringkasan itu sebagai **jurnal memorial** di SIMRS baru.

## 10.2 Bagaimana modul mengenali jurnal ranap lanjutan

Modul mengambil semua jurnal Khanza setelah cut off, lalu mengelompokkannya:

| Kelompok | Ciri |
|---|---|
| **Ranap lanjutan** | No. bukti atau keterangan jurnal memuat no. rawat pasien yang dirawat saat cut off. Khanza memakai no. rawat sebagai no. bukti jurnal tagihan dan pelunasan. |
| **Lainnya** | Jurnal setelah cut off yang tidak terkait pasien tersebut. Contoh: pelunasan piutang lama, pembayaran supplier, atau pengeluaran yang masih dicatat lewat Khanza. |

## 10.3 Membaca halaman

**Kartu angka:**

| Kartu | Arti |
|---|---|
| Dirawat saat cut off | Jumlah pasien ranap lanjutan. |
| Belum pulang | Pasien yang per saat data diambil masih dirawat. |
| Pendapatan setelah cut off | Total pendapatan pasien ranap lanjutan yang dijurnal Khanza setelah cut off. |
| Jurnal ranap lanjutan | Total debet jurnal kelompok ranap lanjutan. |
| Jurnal lain setelah cut off | Jumlah jurnal kelompok Lainnya (hanya tampil bila ada). |

**Pasien dirawat saat cut off:** daftar pasien dengan status sekarang (*Masih dirawat* atau *Pulang tanggal …*), jumlah jurnal, dan pendapatannya setelah cut off. Tanda merah **belum ada jurnal** berarti pasien sudah pulang tetapi Khanza belum membuat jurnal pendapatannya. Periksa apakah tagihannya sudah disimpan di kasir Khanza.

**Ringkasan per bulan per akun COA:** jurnal dikelompokkan per kelompok dan per bulan. Setiap kelompok bulan = **satu jurnal memorial** di SIMRS baru. Nilai sudah dinetokan per akun, jadi debet dan kredit per kelompok selalu sama.

**Jurnal lain setelah cut off:** rincian jurnal kelompok Lainnya.

## 10.4 Langkah kerja

1. Tunggu sampai semua pasien ranap lanjutan pulang dan tagihannya selesai di Khanza. Pantau lewat kartu **Belum pulang**.
2. Di halaman Ringkasan, tekan **Ambil ulang data dari Khanza**.
3. Pastikan tidak ada baris *Belum dipetakan* di ringkasan. Bila ada, petakan rekeningnya (Bab 6).
4. Tekan **Ringkasan per bulan (Excel)**.
5. Untuk setiap bulan kelompok **Ranap lanjutan**, buat satu jurnal memorial di SIMRS baru dengan tanggal akhir bulan (atau tanggal sesuai kebijakan) dan akun serta nilai sesuai Excel.
6. Untuk kelompok **Lainnya**, periksa satu per satu dengan **Rincian jurnal (Excel)**. Bila transaksi itu **sudah** dicatat juga di SIMRS baru, jangan dicatat lagi. Bila belum, catat seperti langkah 5.

<div class="callout"><b>Piutang pasien ranap lanjutan</b><p>Bila pasien ranap lanjutan pulang dengan piutang (misalnya klaim BPJS), jurnal memorial di SIMRS baru menambah saldo akun piutang. Masukkan juga rincian piutang per pasien itu ke buku pembantu piutang SIMRS baru, supaya pelunasannya nanti bisa dicatat per pasien. Datanya ada di sheet <i>Ranap lanjutan rinci</i> (kolom no. rawat).</p></div>

## 10.5 Pendapatan hari rawat sebelum cut off (opsional)

Secara bawaan, seluruh pendapatan pasien ranap lanjutan diakui saat tagihannya dibuat, yaitu setelah cut off. Bila kebijakan Akunting mengakui pendapatan hari rawat sampai 1 November sebagai pendapatan sebelum cut off (akrual):

1. Hitung porsi pendapatan sampai 1 November per pasien.
2. Buat jurnal penyesuaian bertanggal 1 November 2026: debet piutang (atau akun pendapatan yang masih harus diterima), kredit pendapatan rawat inap (Bab 11.4, contoh C).
3. Saat mencatat jurnal memorial ranap lanjutan di SIMRS baru, **kurangi** pendapatan sebesar porsi yang sudah diakui itu, dan kurangi piutang yang sudah diakrualkan.

Pastikan kebijakan ini dipakai konsisten untuk semua pasien ranap lanjutan.
