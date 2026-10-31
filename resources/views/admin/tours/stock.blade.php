{{-- Tur panduan halaman Input stok harian (dimulai lewat tombol Panduan di kanan atas) --}}
@php
  $unitName = \App\Models\StockEntry::LOCATIONS[$loc];
  $steps = [
    ['title' => $opname ? 'Stock opname' : 'Input stok harian', 'text' => $opname
      ? 'Halaman ini dipakai untuk mencocokkan stok di sistem dengan stok fisik di rak. Tur singkat ini menjelaskan setiap bagian. Tekan <b>Lanjut</b> atau tombol panah kanan.'
      : 'Halaman ini menggantikan lembar Excel persediaan. Setiap hari cukup ketik jumlah obat yang <b>masuk</b> dan <b>keluar</b>, sisa stok dihitung otomatis. Tur singkat ini menjelaskan setiap tombol dan kolom. Tekan <b>Lanjut</b> atau tombol panah kanan.'],
    ['el' => '#tKind', 'title' => 'Obat atau BMHP', 'text' => 'Obat dan <b>BMHP</b> (bahan medis habis pakai: abocath, spuit, kasa, benang, reagen, dan sejenisnya) dicatat di daftar terpisah. Pilih jenis barang yang akan diisi. Pilihan ini diingat sampai Anda keluar.'],
    ['el' => '#tUnit', 'title' => 'Pilih unit', 'text' => 'Stok <b>Gudang Farmasi</b> dan <b>Farmasi Sentral</b> dicatat terpisah, sama seperti dua file Excel sebelumnya. Sekarang Anda sedang di <b>'.e($unitName).'</b>. Pilihan ini diingat sampai Anda keluar.'],
    ['el' => '.stk-date', 'title' => 'Pilih tanggal', 'text' => 'Tanggal otomatis <b>hari ini</b>. Pakai tombol panah untuk pindah ke hari sebelumnya bila ada isian yang terlewat, atau klik kotak tanggal. Tanggal ke depan tidak bisa dipilih.'],
    ['el' => '#tMode', 'title' => 'Jenis isian', 'text' => '<ul><li><b>Masuk & keluar</b>: isian harian biasa.</li><li><b>Stock opname</b>: isi hasil hitung fisik di rak. Lakukan sekali di awal pemakaian sistem, lalu setiap jadwal SO (misalnya akhir bulan).</li></ul>'],
    ['el' => '#tNote', 'title' => 'Petunjuk singkat', 'text' => 'Kotak ini menunjukkan berapa obat yang sudah diisi pada tanggal ini dan siapa yang terakhir mengisi, jadi petugas shift berikutnya tahu sampai mana.'],
    ['el' => '#stkQ', 'title' => 'Cari obat', 'text' => 'Ketik sebagian nama, misalnya <i>amox 500</i>. Daftar langsung tersaring. Tekan tombol <b>/</b> di keyboard untuk langsung ke kotak ini, lalu <b>Enter</b> untuk lompat ke kolom isian obat pertama yang cocok.'],
    ['el' => '#stkG', 'title' => 'Saring golongan', 'text' => 'Tampilkan satu golongan saja, misalnya INJEKSI atau TABLET, sesuai urutan rak.'],
    ['el' => '#tFilled', 'title' => 'Hanya yang sudah diisi', 'text' => 'Centang untuk memeriksa ulang obat yang sudah diisi pada tanggal ini sebelum pulang shift.'],
  ];
  if ($opname) {
    $steps[] = ['el' => '.stk-row .sys', 'title' => 'Stok sistem', 'text' => 'Stok menurut catatan sistem pada awal hari ini, sebelum ada transaksi.'];
    $steps[] = ['el' => '.stk-row input.q[data-f=counted]', 'title' => 'Hasil hitung', 'text' => 'Ketik jumlah fisik di rak dalam <b>satuan terkecil</b> (tablet, ampul, vial, botol). Hitung <b>sebelum</b> ada barang masuk atau keluar hari ini. Kosongkan bila obat tidak dihitung.'];
    $steps[] = ['el' => '.stk-row .diff', 'title' => 'Selisih', 'text' => 'Muncul otomatis: <b style="color:var(--green)">0</b> berarti cocok, angka oranye berarti ada selisih antara fisik dan sistem. Selisih tercatat di Rekap persediaan.'];
    $steps[] = ['el' => '.stk-row input.dt', 'title' => 'Tanggal ED', 'text' => 'Isi tanggal kedaluwarsa terdekat dari kemasan yang ada di rak. Obat dengan ED kurang dari 90 hari diberi tanda oranye di semua halaman.'];
  } else {
    $steps[] = ['el' => '.stk-row td.n', 'title' => 'Stok awal', 'text' => 'Sisa stok kemarin, atau hasil stock opname bila pagi ini dihitung. Kolom ini tidak perlu diisi.'];
    $steps[] = ['el' => '.stk-row input.q[data-f=in]', 'title' => 'Masuk', 'text' => $loc === 'gudang'
      ? 'Jumlah barang yang <b>diterima gudang</b> hari ini, misalnya dari PBF atau distributor, dalam satuan terkecil. Kosong berarti 0. Tekan <b>Enter</b> untuk turun ke baris berikutnya, seperti di Excel.'
      : 'Jumlah barang yang <b>diterima dari gudang</b> hari ini, dalam satuan terkecil. Kosong berarti 0. Tekan <b>Enter</b> untuk turun ke baris berikutnya, seperti di Excel.'];
    if ($loc === 'sentral' && $fromGudang) {
      $steps[] = ['el' => '.stk-row .fill', 'title' => 'Bantuan isi dari gudang', 'text' => 'Tulisan biru ini menunjukkan jumlah yang dikeluarkan Gudang Farmasi hari ini untuk obat tersebut. Klik untuk langsung mengisi kolom Masuk.'];
    }
    $steps[] = ['el' => '.stk-row input.q[data-f=out]', 'title' => 'Keluar', 'text' => $loc === 'gudang'
      ? 'Jumlah barang yang <b>dikeluarkan gudang</b> hari ini, misalnya ke Farmasi Sentral atau ruangan. Tidak bisa melebihi stok.'
      : 'Jumlah obat yang <b>diberikan</b> hari ini ke pasien rawat jalan, rawat inap, IGD, atau ruangan. Tidak bisa melebihi stok.'];
    $steps[] = ['el' => '.stk-row .end', 'title' => 'Sisa', 'text' => 'Dihitung langsung saat Anda mengetik: stok awal + masuk − keluar.<ul><li><b style="color:var(--amber)">Oranye</b>: stok menipis, sudah waktunya pesan.</li><li><b style="color:var(--red)">Merah</b>: habis atau minus, periksa isian.</li></ul>'];
  }
  $steps[] = ['el' => '.stk-row .nt', 'title' => 'Catatan', 'text' => 'Opsional. Tulis asal atau tujuan barang, nomor faktur, atau keterangan lain. Muncul di kartu stok obat.'];
  $steps[] = ['el' => '#stkSave', 'title' => 'Simpan', 'text' => 'Baris yang diubah ditandai garis biru di kiri dan jumlahnya tampil di sini. Klik <b>Simpan</b> setelah selesai. Hanya baris yang diubah yang disimpan. <b>Batalkan</b> mengembalikan semua angka seperti semula. Bila Anda pindah halaman sebelum menyimpan, sistem akan bertanya dulu.'];
  $steps[] = ['el' => '#tRekap', 'title' => 'Rekap persediaan', 'text' => 'Lihat stok akhir semua obat, obat yang perlu dipesan, ED dekat, dan nilai stok per bulan. Rekap bisa diunduh untuk Excel. Isian di sini juga dipakai untuk dicocokkan dengan SIMRS selama masa pengisian paralel.'];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'stock'.($opname ? '-opname' : ''), 'guide' => route('admin.help').'#stock', 'steps' => $steps]);</script>@endpush
