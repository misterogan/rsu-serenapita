{{-- Tur panduan halaman Data pasien (pencarian) --}}
@php
  $steps = [
    ['title' => 'Data pasien', 'text' => 'Data pasien dan riwayat berobat dibaca langsung dari SIMRS Khanza, jadi tidak perlu membuka aplikasi Khanza. Halaman ini <b>hanya untuk melihat</b>; perubahan data tetap dilakukan di SIMRS. Setiap pembukaan data pasien tercatat atas nama Anda.'],
    ['el' => '#tSearch', 'title' => 'Cari pasien', 'text' => 'Ketik <b>No. RM</b>, <b>NIK</b>, <b>No. kartu BPJS</b>, <b>nomor HP</b>, atau <b>nama</b> (boleh sebagian, misalnya <i>maria sitorus</i>), lalu tekan Enter. Minimal 3 huruf atau angka.'],
    ['el' => '#tToday', 'title' => 'Kunjungan hari ini', 'text' => 'Tanpa pencarian, tampil daftar pasien yang mendaftar hari ini di SIMRS Khanza. Pakai panah atau kotak tanggal untuk melihat hari lain. Klik baris untuk membuka data pasien.'],
    ['el' => '#tResults', 'title' => 'Hasil pencarian', 'text' => 'Klik nama atau baris pasien untuk membuka identitas lengkap dan riwayat kunjungannya.'],
  ];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'patients', 'guide' => route('admin.help').'#patients', 'steps' => $steps]);</script>@endpush
