CARA MEMPERBARUI PANDUAN BACKOFFICE
===================================

Isi menu Bantuan → Panduan diambil dari file Markdown di folder ini, satu file untuk satu bab.
Bab yang ditampilkan dan urutannya ditentukan oleh file index.json.

Untuk mengubah isi panduan:
1. Buka file bab yang sesuai dengan teks editor (misalnya VS Code atau Notepad).
2. Ubah teksnya. Format: # judul bab, ## judul bagian, ### sub-bagian, - poin, 1. langkah, tabel | a | b |.
   Kotak warna: <div class="callout tips"><b>Tips</b><p>Isi</p></div> (tips, catatan, awas, penting).
   Gambar: simpan di public/docs/img lalu tulis <figure><img src="/docs/img/nama.jpg" alt="..."><figcaption>...</figcaption></figure>
3. Simpan, lalu git add, commit, dan push.

Untuk menambah bab baru, buat file baru (misalnya 32-nama.md) yang diawali baris
<a id="nama" class="anchor"></a>, lalu tambahkan nama filenya ke index.json.
