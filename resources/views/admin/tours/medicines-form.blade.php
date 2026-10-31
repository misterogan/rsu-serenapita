{{-- Tur panduan formulir tambah/ubah obat --}}
@php
  $steps = [
    ['title' => 'Formulir obat', 'text' => 'Isi data obat sekali saja. Jumlah stok tidak diisi di sini, melainkan di Input stok harian.'],
    ['el' => '.fld:has(#f_name)', 'title' => 'Nama obat', 'text' => 'Tulis lengkap dengan kekuatan dan bentuk sediaan, misalnya <i>AMOXICILLIN 500 MG TABLET</i>, supaya mudah dicari. Nama tidak boleh sama dengan obat lain.'],
    ['el' => '.fld:has(#f_group)', 'title' => 'Golongan', 'text' => 'Menentukan kelompok obat di lembar isian, sama seperti pembagian di file Excel (INJEKSI, SIRUP, TABLET, KULKAS, NARKOTIKA, HIGH ALERT, dan seterusnya).'],
    ['el' => '.fld:has(#f_unit)', 'title' => 'Satuan terkecil', 'text' => 'Satuan yang dipakai saat menghitung masuk, keluar, dan stock opname, misalnya Tablet, Ampul, Vial, atau Botol.'],
    ['el' => '.fld:has(#f_price)', 'title' => 'Harga', 'text' => 'Harga per satuan terkecil termasuk PPN. Cukup ketik angkanya, titik pemisah ribuan muncul otomatis (contoh: 13.924). Gunakan koma untuk sen. Dipakai untuk menghitung nilai stok di Rekap persediaan. Boleh dikosongkan bila belum tahu.'],
    ['el' => '.fld:has(#f_expired_at)', 'title' => 'Tanggal ED terdekat', 'text' => 'Tanggal kedaluwarsa kemasan yang paling dekat. Bisa juga diperbarui saat stock opname.'],
    ['el' => '.tog-row', 'title' => 'Obat aktif', 'text' => 'Obat aktif muncul di lembar Input stok harian.'],
    ['el' => '.save-bar', 'title' => 'Simpan', 'text' => 'Klik <b>Simpan</b> untuk menyimpan, atau <b>Batal</b> untuk kembali tanpa menyimpan.'],
  ];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'medicines-form', 'guide' => route('admin.help').'#stock-4', 'steps' => $steps]);</script>@endpush
