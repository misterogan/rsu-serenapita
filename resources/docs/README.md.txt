CARA MEMPERBARUI PANDUAN BACKOFFICE
===================================

Isi menu Bantuan → Panduan diambil dari file Markdown di folder ini,
satu file untuk satu bab. Urutan bab mengikuti nomor di depan nama file.

Untuk mengubah isi panduan:
1. Buka file bab yang sesuai dengan teks editor (misalnya VS Code atau Notepad).
2. Ubah teksnya. Format yang dipakai:
     # Judul bab
     ## Judul bagian
     ### Judul sub-bagian
     - poin
     1. langkah bernomor
     | Kolom | Kolom |   (tabel)
     <b>teks tebal</b>
   Kotak warna:
     <div class="callout tips"><b>Tips</b><p>Isi tips</p></div>
     (ganti "tips" dengan catatan, awas, atau penting)
   Gambar: simpan file di public/docs/img lalu tulis
     <figure><img src="/docs/img/nama.jpg" alt="Keterangan"><figcaption>Keterangan</figcaption></figure>
3. Simpan, lalu git add, commit, dan push. Panduan di backoffice ikut berubah setelah auto-deploy.

Untuk menambah bab baru, buat file baru dengan nomor urut, misalnya 28-nama.md,
dan awali dengan baris: <a id="nama" class="anchor"></a>
