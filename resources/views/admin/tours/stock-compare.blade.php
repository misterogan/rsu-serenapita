{{-- Tur panduan halaman Cocokkan SIMRS --}}
@php
  $steps = [
    ['title' => 'Cocokkan SIMRS', 'text' => 'Selama masa pengisian paralel, halaman ini memastikan stok di sistem ini sama dengan stok di SIMRS. Pencocokan memakai <b>kode obat SIMRS</b>, jadi tetap benar walaupun nama obat diubah di SIMRS.'],
    ['el' => '#tUpload', 'title' => 'Unggah file SIMRS', 'text' => 'Unduh laporan stok per unit dari SIMRS (format sama dengan file SO: kode, nama, satuan, stok). Pilih file, pilih <b>unit</b> yang sesuai, dan <b>tanggal</b> file itu diunduh, lalu klik <b>Unggah dan cocokkan</b>.'],
    ['el' => '#tInfo', 'title' => 'File yang sedang dicocokkan', 'text' => 'Menunjukkan file, sheet, unit, dan tanggal yang dibandingkan. Hasil tersimpan 4 jam; klik <b>Hapus hasil</b> bila sudah selesai.'],
    ['el' => '#tStats', 'title' => 'Ringkasan', 'text' => '<ul><li><b>Selisih</b>: angka stok berbeda. Buka kartu stok obatnya untuk mencari tanggal yang salah isi.</li><li><b>Cocok</b>: angka sama.</li><li><b>Beda daftar</b>: kode ada di SIMRS tapi belum ada di sistem, atau sebaliknya.</li><li><b>Nama berubah</b>: kode sama, nama di SIMRS sudah diganti.</li></ul>Klik kartu untuk menyaring tabel.'],
    ['el' => '#tActs', 'title' => 'Samakan daftar', 'text' => '<b>Samakan nama</b> mengganti nama di sistem sesuai SIMRS. <b>Tambahkan kode baru</b> memasukkan kode SIMRS yang belum ada (stok 0, tersembunyi sampai diisi). Keduanya tidak mengubah angka stok.'],
    ['el' => '#cmpTbl thead', 'title' => 'Tabel perbandingan', 'text' => '<b>Selisih</b> = stok sistem dikurangi stok SIMRS. Angka minus berarti sistem lebih sedikit dari SIMRS. Klik nama obat untuk membuka kartu stoknya.'],
    ['el' => '#tCsv', 'title' => 'Unduh hasil', 'text' => 'Unduh hasil perbandingan untuk dilaporkan atau ditindaklanjuti di Excel.'],
  ];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'stock-compare', 'guide' => route('admin.help').'#stock-compare', 'steps' => $steps]);</script>@endpush
