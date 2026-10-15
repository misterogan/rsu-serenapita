<a id="doctors" class="anchor"></a>

# Bab 12. Profil dokter

<p class="lead">Menu untuk mengelola dokter yang tampil di website: nama, poli, status, jadwal praktik, foto, dan profil singkat.</p>

<div class="callout penting"><b>Penting</b><p>Website punya dua <b>mode sumber data</b>, diatur di <b>Sistem → Integrasi SIMRS</b>. <b>Mode manual</b> (dipakai sekarang, selama SIMRS belum terhubung): dokter, poli, jadwal, cuti, dan status janji temu diisi di backoffice. <b>Mode SIMRS</b>: data tersebut diambil otomatis dari SIMRS dan terkunci di backoffice. Lihat [Bab 20](#simrs).</p></div>

<figure><a href="/docs/img/doctors.jpg" target="_blank"><img src="/docs/img/doctors.jpg" width="1200" height="906" alt="Gambar 12.1  Daftar dokter." loading="lazy"></a><figcaption>Gambar 12.1  Daftar dokter.</figcaption></figure>

<a id="doctors-1" class="anchor"></a>

## 12.1 Mode manual: menambah dan mengubah dokter

1. Klik menu <b>Pelayanan medis → Profil dokter</b>.
2. Klik <b>+ Tambah dokter</b>, atau ikon pensil untuk mengubah dokter yang sudah ada.
3. Isi nama lengkap dan gelar, pilih <b>Poliklinik</b> dan <b>Status</b>.
4. Isi <b>Jadwal praktik</b>: pilih hari, lalu jam mulai dan jam selesai. Klik <b>+ Tambah jadwal</b> untuk menambah baris. Klik <b>×</b> untuk menghapus baris.
5. Lengkapi foto, warna avatar, dan profil singkat, lalu klik <b>Simpan</b>.

<figure><a href="/docs/img/doctor_form_manual.jpg" target="_blank"><img src="/docs/img/doctor_form_manual.jpg" width="1200" height="1277" alt="Gambar 12.2  Formulir dokter dalam mode manual, lengkap dengan pengisian jadwal praktik." loading="lazy"></a><figcaption>Gambar 12.2  Formulir dokter dalam mode manual, lengkap dengan pengisian jadwal praktik.</figcaption></figure>

| Kolom / tombol | Penjelasan | Aturan |
|---|---|---|
| <b>Nama lengkap dan gelar</b> | Contoh: dr. Maria Situmorang, Sp.A. | Wajib, maks. 120 karakter |
| <b>Poliklinik</b> | Poli tempat dokter praktik. Tambahkan poli lebih dulu di menu Poliklinik bila belum ada. | Wajib |
| <b>Status</b> | <b>Aktif</b>: tampil dan bisa dipilih pasien. <b>Cuti</b>: tetap tampil. <b>Nonaktif</b>: tidak tampil di website. | Wajib |
| <b>Jadwal praktik</b> | Satu baris untuk satu sesi. Bila dokter praktik dua kali dalam sehari, misalnya Jumat 10.00–12.00 dan 13.30–19.30, isi <b>dua baris</b> dengan hari yang sama. Jam mulai harus lebih awal dari jam selesai. | Maks. 21 baris |
| <b>Foto dokter</b> | Foto wajah dalam lingkaran. Centang <b>Hapus gambar</b> untuk kembali ke avatar inisial. | Opsional, JPG, PNG, atau WebP, maks. 2 MB. Lihat [Bab 25](#gambar). |
| <b>Warna avatar</b> | Warna lingkaran berisi inisial nama, dipakai bila tidak ada foto. | Wajib dipilih |
| <b>Profil singkat</b> | Pendidikan, pengalaman, atau keahlian khusus. | Maks. 600 karakter |
| <b>Kode dokter di SIMRS</b> | Boleh dikosongkan sekarang. Isi nanti saat SIMRS akan dihubungkan, supaya foto dan profil dokter ini tetap terpakai setelah sinkron. | Opsional |
| <b>Tampilkan di situs</b> | Matikan untuk menyembunyikan dokter dari halaman Dokter, Jadwal, dan formulir janji temu. | Sakelar |

<div class="callout tips"><b>Tips</b><p>Untuk dokter yang berhenti praktik, ubah status menjadi <b>Nonaktif</b>. Dokter yang sudah memiliki janji temu tidak bisa dihapus, supaya riwayat janji tetap utuh.</p></div>

<a id="doctors-2" class="anchor"></a>

## 12.2 Mode SIMRS: melengkapi tampilan dokter

Dalam mode SIMRS, nama, gelar, poli, status, dan jadwal praktik terkunci dan bertanda "dari SIMRS". Yang bisa diubah hanya foto, warna avatar, profil singkat, dan sakelar Tampil. Bila ada data yang salah, perbaiki di SIMRS.

<div class="callout catatan"><b>Catatan</b><p>Dokter berstatus <b>Nonaktif</b>, atau dokter dari poli yang disembunyikan, otomatis tidak tampil di website walaupun sakelarnya hijau.</p></div>
