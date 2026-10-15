<a id="chatbot" class="anchor"></a>

# Bab 18. Chatbot Serena

<p class="lead">Serena adalah asisten otomatis di website yang menjawab pertanyaan umum selama 24 jam, tanpa petugas. Serena mencocokkan kata kunci dalam pertanyaan dengan data di database dan daftar FAQ.</p>

<a id="chatbot-1" class="anchor"></a>

## 18.1 Yang sudah bisa dijawab otomatis

Tanpa pengaturan tambahan, Serena sudah bisa menjawab pertanyaan berikut, karena datanya diambil langsung dari database:

- Jadwal dokter berdasarkan nama, termasuk hari tertentu. Contoh: "dr maria besok praktik?"
- Jadwal poli berdasarkan nama poli atau keluhan. Contoh: "poli gigi hari sabtu" atau "mau periksa usg".
- Dokter yang praktik pada hari tertentu, dan dokter yang sedang cuti.
- Daftar dokter, poli, dan layanan. Contoh: "ada rontgen?" atau "apotek buka?"
- Jam besuk, syarat BPJS, cara membuat janji, jam loket, alamat, dan nomor kontak.
- Kata-kata darurat, seperti pingsan, kejang, nyeri dada, atau kecelakaan, dijawab dengan arahan ke IGD beserta nomornya.
- Singkatan dan ejaan santai, seperti "jdwl dktr", "ga", "bsk", dan "yg", serta salah ketik satu huruf pada nama dokter.

<a id="chatbot-2" class="anchor"></a>

## 18.2 Pengaturan chatbot

<figure><a href="/docs/img/chatbot.jpg" target="_blank"><img src="/docs/img/chatbot.jpg" width="1200" height="1410" alt="Gambar 18.1  Pengaturan chatbot dan panel uji coba." loading="lazy"></a><figcaption>Gambar 18.1  Pengaturan chatbot dan panel uji coba.</figcaption></figure>

| Kolom / tombol | Penjelasan | Aturan |
|---|---|---|
| <b>Aktifkan chatbot</b> | Bila dimatikan, panel chat di website hanya berisi live chat petugas. | Sakelar |
| <b>Nama asisten</b> | Nama yang tampil di panel chat. Bawaannya "Serena". | Wajib, maks. 30 karakter |
| <b>Sapaan pembuka</b> | Pesan pertama saat pengunjung membuka chat. | Wajib, maks. 400 karakter |
| <b>Pertanyaan cepat</b> | Tombol pertanyaan di bawah kotak chat. Tulis satu pertanyaan per baris. | Maks. 600 karakter |
| <b>Informasi tambahan (FAQ)</b> | Daftar jawaban buatan sendiri. Format ditulis di bawah tabel ini. | Maks. 8.000 karakter |
| <b>Pesan darurat</b> | Jawaban bila pesan berisi kata-kata darurat. Nomor IGD ditambahkan otomatis. | Wajib, maks. 300 karakter |
| <b>Jawaban bila tidak paham</b> | Jawaban bila pertanyaan tidak dikenali. Bila kosong, Serena menyarankan topik yang bisa ditanyakan dan mengarahkan ke live chat. | Maks. 600 karakter |

<a id="chatbot-3" class="anchor"></a>

## 18.3 Menulis FAQ

Tulis <b>satu entri per baris</b> dengan format:

<div class="callout catatan"><b>Catatan</b><p><b>kata kunci, kata kunci, kata kunci: jawaban lengkap</b></p></div>

Contoh:

| Baris FAQ | Pertanyaan yang akan dijawab |
|---|---|
| parkir, motor, mobil: Parkir tersedia di halaman depan dan gratis untuk pasien. | "parkirnya bayar?", "bawa motor parkir dimana?" |
| tarif kamar, harga kamar, vip: Tarif kamar VIP Rp500.000 per malam. Info lengkap hubungi pendaftaran. | "berapa harga kamar vip?" |
| kantin, makan: Kantin buka setiap hari pukul 07.00 – 19.00 di samping lobi. | "ada kantin?" |

- Kata kunci dipisah dengan <b>koma</b>, dan jawaban dimulai setelah <b>titik dua</b>.
- Gunakan kata yang biasa diketik pasien, termasuk singkatan, misalnya "ruang rawat" dan "kamar inap".
- Entri FAQ dicek lebih dulu sebelum jawaban otomatis. Jadi FAQ bisa dipakai untuk mengganti jawaban bawaan.
- Jangan memakai kata kunci yang terlalu umum, seperti "rs" atau "dokter", karena akan cocok dengan terlalu banyak pertanyaan.

<a id="chatbot-4" class="anchor"></a>

## 18.4 Mencoba chatbot

1. Klik <b>Simpan chatbot</b> lebih dulu. Panel uji coba memakai pengaturan yang sudah disimpan.
2. Ketik pertanyaan di kolom <b>Tulis pertanyaan uji</b> di panel kanan, lalu tekan Enter.
3. Periksa jawabannya. Kalau kurang tepat, tambahkan atau perbaiki FAQ, lalu simpan dan coba lagi.

<figure><a href="/docs/img/chatbot_test.jpg" target="_blank"><img src="/docs/img/chatbot_test.jpg" width="1200" height="906" alt="Gambar 18.2  Contoh uji coba jawaban chatbot." loading="lazy"></a><figcaption>Gambar 18.2  Contoh uji coba jawaban chatbot.</figcaption></figure>

<div class="callout awas"><b>Hati-hati</b><p>Serena hanya memberi informasi umum, bukan diagnosis atau saran obat. Untuk pertanyaan medis, Serena akan mengarahkan pasien ke poli atau live chat petugas.</p></div>
