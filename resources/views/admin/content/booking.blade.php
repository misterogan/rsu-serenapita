@extends('layouts.admin', ['current' => 'appointment-form'])
@section('title', 'Formulir janji temu')
@section('content')
<form method="post">
@csrf
<x-admin-head module="appointment-form" desc="Mengatur formulir Buat Janji di situs. Janji yang dikirim pasien diteruskan ke SIMRS.">
  <button class="btn pri">Simpan formulir</button>
</x-admin-head>
@if(!\App\Support\DataSource::manual())<x-sync-note title="Kuota dan slot diatur di SIMRS">Jam tersedia, kuota per dokter, dan nomor antrean mengikuti SIMRS, sehingga situs dan loket memakai aturan yang sama.</x-sync-note>@endif
<div class="card"><div class="card-b form">
  @foreach([['online', 'Pendaftaran online', 'Matikan untuk menutup formulir sementara. Halaman Buat Janji akan menampilkan nomor telepon.'], ['require_nik', 'Wajibkan NIK', 'Untuk mencocokkan pasien di SIMRS.'], ['require_bpjs', 'Wajibkan nomor kartu BPJS dan rujukan', 'Berlaku jika pasien memilih bayar BPJS.'], ['rujukan', 'Ingatkan rujukan untuk pasien BPJS', 'Tampilkan pesan membawa surat rujukan setelah janji dibuat.']] as [$k, $t, $d])
  <div class="fld"><div class="tog-row"><div><b>{{ $t }}</b><small>{{ $d }}</small></div><label class="switch"><input type="checkbox" name="{{ $k }}" value="1" @checked($b[$k])><span></span></label></div></div>
  @endforeach
  @if(\App\Support\DataSource::manual())
  <div class="fld half"><label for="interval">Jarak jam kedatangan</label><select id="interval" name="interval">@foreach([10, 15, 20, 30, 60] as $n)<option value="{{ $n }}" @selected(($b['interval'] ?? 30) == $n)>Setiap {{ $n }} menit</option>@endforeach</select><div class="hint">Contoh: jadwal 08.00–10.00 dengan jarak 30 menit menghasilkan pilihan 08.00, 08.30, 09.00, dan 09.30.</div></div>
  <div class="fld half"><label for="slot_quota">Kuota pasien per jam kedatangan</label><input id="slot_quota" type="number" name="slot_quota" min="1" max="50" value="{{ old('slot_quota', $b['slot_quota'] ?? 3) }}"><div class="hint">Jam yang sudah penuh tidak bisa dipilih lagi. Berlaku dalam mode data manual.</div></div>
  @endif
  <div class="fld"><label for="consent">Teks persetujuan data pasien</label><textarea id="consent" name="consent" rows="3">{{ old('consent', $b['consent']) }}</textarea><div class="hint">Wajib dicentang pasien sebelum mengirim formulir.</div></div>
  <div class="fld"><label for="note">Pesan setelah janji dibuat</label><textarea id="note" name="note" rows="3">{{ old('note', $b['note']) }}</textarea><div class="hint">Kode janji dan nomor antrean dari SIMRS tampil otomatis di atas pesan ini.</div></div>
</div></div>
</form>
@endsection
