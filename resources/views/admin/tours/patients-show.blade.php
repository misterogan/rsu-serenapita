{{-- Tur panduan halaman profil pasien --}}
@php
  $steps = [
    ['title' => 'Profil pasien', 'text' => 'Ringkasan lengkap satu pasien dari SIMRS Khanza: identitas, ringkasan klinis, dan semua kunjungannya.'],
    ['el' => '#tAlergi', 'title' => 'Riwayat alergi', 'text' => 'Muncul berwarna merah bila pernah tercatat alergi di pemeriksaan rawat jalan. Selalu perhatikan bagian ini.'],
    ['el' => '#tIdentity', 'title' => 'Identitas', 'text' => 'Data dari pendaftaran: NIK, tanggal lahir, alamat, kontak, penanggung jawab, dan cara bayar.'],
    ['el' => '#tVital', 'title' => 'Tanda vital terakhir', 'text' => 'Tensi, nadi, suhu, SpO2, berat, dan tinggi dari pemeriksaan terakhir.'],
    ['el' => '#tVisits', 'title' => 'Riwayat kunjungan', 'text' => 'Semua kunjungan dari yang terbaru, lengkap dengan poli, dokter, diagnosa utama, dan ruang rawat inap. Saring per jenis atau tahun, lalu klik baris untuk melihat detail kunjungan.'],
    ['el' => '.vh-act .btn:last-child', 'title' => 'Cetak', 'text' => 'Mencetak halaman ini tanpa menu samping. Simpan hasil cetak dengan aman karena berisi data rahasia pasien.'],
  ];
@endphp
@push('tour')<script>window.TOUR = @json(['key' => 'patients-show', 'guide' => route('admin.help').'#patients', 'steps' => $steps]);</script>@endpush
