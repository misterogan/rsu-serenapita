<a id="appointments" class="anchor"></a>

# Bab 15. Janji temu

<p class="lead">Daftar janji temu yang dibuat pasien lewat website. Dalam mode manual, status janji diubah di sini. Dalam mode SIMRS, janji diteruskan ke SIMRS dan halaman ini dipakai untuk memantau.</p>

<figure><a href="/docs/img/appointments.jpg" target="_blank"><img src="/docs/img/appointments.jpg" width="1200" height="906" alt="Gambar 15.1  Daftar janji temu." loading="lazy"></a><figcaption>Gambar 15.1  Daftar janji temu.</figcaption></figure>

| Kolom | Keterangan |
|---|---|
| Kode | Kode janji dari website, misalnya <b>SRN-AB12CD</b>. Di bawahnya tertera kode booking dari SIMRS. |
| Pasien | Nama, nomor HP, dan nomor rekam medis bila diisi. |
| Dokter | Nama dokter dan polinya. |
| Waktu | Hari, tanggal, jam kedatangan, dan nomor antrean dari SIMRS. |
| Bayar | BPJS Kesehatan, Umum, atau Asuransi lain. |
| Status SIMRS | <b>Menunggu</b>, <b>Dikonfirmasi</b>, <b>Hadir</b>, <b>Selesai</b>, atau <b>Batal</b>. Diperbarui dari SIMRS. |
| Pengiriman | <b>Terkirim</b> berarti janji sudah tercatat di SIMRS. <b>Gagal</b> berarti SIMRS tidak bisa dihubungi saat pasien mendaftar. |

<a id="appointments-1" class="anchor"></a>

## 15.1 Mencari dan menyaring janji temu

- Ketik kode janji, nama pasien, nomor HP, atau kode booking SIMRS di kolom cari, lalu tekan Enter.
- Pilih status di pilihan <b>Semua status</b>, misalnya hanya yang Menunggu.
- Pilih tanggal di kolom tanggal untuk melihat janji pada hari tertentu.

<a id="appointments-2" class="anchor"></a>

## 15.2 Janji yang gagal terkirim ke SIMRS (mode SIMRS)

1. Cari janji dengan tanda merah <b>Gagal</b> di kolom Pengiriman. Arahkan mouse ke tulisan Gagal untuk melihat penyebabnya.
2. Klik tombol <b>Kirim ulang</b> di sebelahnya.
3. Bila berhasil, tanda berubah menjadi <b>Terkirim</b>. Bila masih gagal, hubungi tim IT, lalu hubungi pasien lewat telepon untuk konfirmasi manual.

<a id="appointments-3" class="anchor"></a>

## 15.3 Mengubah status janji (mode manual)

1. Klik ikon pensil di baris janji temu.
2. Periksa data pasien, dokter, waktu, dan keluhan.
3. Ubah <b>Status janji</b>: <b>Dikonfirmasi</b> setelah dicek petugas, <b>Hadir</b> saat pasien datang, <b>Selesai</b> setelah dilayani, atau <b>Batal</b>.
4. Klik <b>Simpan</b>.

<figure><a href="/docs/img/appt_edit_manual.jpg" target="_blank"><img src="/docs/img/appt_edit_manual.jpg" width="1200" height="906" alt="Gambar 15.2  Mengubah status janji temu dalam mode manual." loading="lazy"></a><figcaption>Gambar 15.2  Mengubah status janji temu dalam mode manual.</figcaption></figure>

- Nomor antrean dibuat otomatis per dokter per tanggal, diawali huruf depan nama poli, misalnya <b>A-001</b> untuk Poli Anak.
- Janji berstatus <b>Batal</b> membebaskan kembali kuota jam tersebut.

<div class="callout penting"><b>Penting</b><p>Dalam mode SIMRS, konfirmasi, kehadiran, dan pembatalan dilakukan di SIMRS. Backoffice hanya untuk memantau, supaya data di loket dan di website selalu sama.</p></div>
