@extends('layouts.admin', ['current' => 'appointment-form'])
@section('title', 'Formulir janji temu')
@section('content')
<form method="post">
@csrf
<x-admin-head module="appointment-form" desc="Mengatur formulir Buat Janji di situs. Janji yang dikirim pasien diteruskan ke SIMRS.">
  <button class="btn pri">Simpan formulir</button>
</x-admin-head>
<x-sync-note title="Kuota dan slot diatur di SIMRS">Jam tersedia, kuota per dokter, dan nomor antrean mengikuti SIMRS, sehingga situs dan loket memakai aturan yang sama.</x-sync-note>
<div class="card"><div class="card-b form">
  @foreach([['online', 'Pendaftaran online', 'Matikan untuk menutup formulir sementara. Halaman Buat Janji akan menampilkan nomor telepon.'], ['require_nik', 'Wajibkan NIK', 'Untuk mencocokkan pasien di SIMRS.'], ['require_bpjs', 'Wajibkan nomor kartu BPJS dan rujukan', 'Berlaku jika pasien memilih bayar BPJS.'], ['rujukan', 'Ingatkan rujukan untuk pasien BPJS', 'Tampilkan pesan membawa surat rujukan setelah janji dibuat.']] as [$k, $t, $d])
  <div class="fld"><div class="tog-row"><div><b>{{ $t }}</b><small>{{ $d }}</small></div><label class="switch"><input type="checkbox" name="{{ $k }}" value="1" @checked($b[$k])><span></span></label></div></div>
  @endforeach
  <div class="fld"><label for="consent">Teks persetujuan data pasien</label><textarea id="consent" name="consent" rows="3">{{ old('consent', $b['consent']) }}</textarea><div class="hint">Wajib dicentang pasien sebelum mengirim formulir.</div></div>
  <div class="fld"><label for="note">Pesan setelah janji dibuat</label><textarea id="note" name="note" rows="3">{{ old('note', $b['note']) }}</textarea><div class="hint">Kode janji dan nomor antrean dari SIMRS tampil otomatis di atas pesan ini.</div></div>
</div></div>
</form>
@endsection
