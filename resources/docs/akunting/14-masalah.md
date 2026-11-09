<a id="cl-masalah" class="anchor"></a>

# Bab 14. Pertanyaan dan masalah umum

**Data Khanza tidak bisa diambil: "Koneksi Khanza belum diatur" atau "Tidak bisa terhubung".**
Server website belum tersambung ke database Khanza, atau server Khanza mati. Hubungi tim IT. Bila salinan data sudah pernah diambil, closing tetap bisa dilanjutkan dengan data itu.

**Ada pesan "bagian tidak bisa dibaca dari Khanza".**
Struktur tabel Khanza berbeda dengan yang diharapkan. Bagian lain tetap bisa dipakai. Kirim detail error (di kartu Data Khanza) ke tim IT.

**Proses ambil data lama sekali atau gagal karena waktu habis.**
Biasanya pada bagian persediaan, karena riwayat stok sangat banyak. Minta tim IT menaikkan `CLOSING_QUERY_TIMEOUT`, atau pakai nilai persediaan dari stock opname dan buat penyesuaian.

**Angka berubah padahal tidak ada yang diubah.**
Data Khanza diambil ulang dan ada transaksi Khanza yang berubah (misalnya kasir mengubah jurnal di Khanza untuk periode sebelum cut off). Bandingkan dengan paket closing yang diunduh sebelumnya. Mengunci closing mencegah hal ini.

**Saran pemetaan salah.**
Saran hanya berdasarkan kata pada nama rekening. Ganti kodenya di kotak Akun COA baru lalu simpan.

**Neraca saldo tidak seimbang setelah semua rekening dipetakan.**
Lihat Bab 7.3. Penyebab paling umum adalah jurnal Khanza tidak seimbang atau saldo awal tahun yang tidak seimbang.

**Piutang tidak cocok dengan rincian.**
Lihat penyebab umum di Bab 9.2. Unduh rincian piutang, bandingkan per penjamin dengan laporan piutang Khanza, lalu cari transaksi yang hanya ada di salah satu sisi.

**Ada pendaftaran pasien baru di Khanza setelah go-live.**
Pasien itu seharusnya didaftarkan di SIMRS baru. Koordinasikan dengan bagian pendaftaran dan pastikan transaksinya tidak hilang.

**Pasien ranap lanjutan sudah pulang tetapi ditandai "belum ada jurnal".**
Tagihan pasien belum disimpan di kasir Khanza, atau Khanza tidak membuat jurnal dengan no. rawat sebagai no. bukti. Periksa di Khanza, lalu ambil ulang data.

**Bisa closing untuk tahun selain 2026?**
Bisa, dengan mengubah awal tahun buku dan tanggal cut off. Rentang paling panjang satu tahun.

**Komisaris tidak bisa mengubah apa pun.**
Memang begitu. Komisaris hanya bisa melihat dan mengunduh. Semua perubahan dilakukan dengan akun Akunting.
