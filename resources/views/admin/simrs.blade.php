@extends('layouts.admin', ['current' => 'simrs'])
@section('title', 'Integrasi SIMRS')
@section('content')
<x-admin-head module="simrs" desc="SIMRS adalah sumber utama data poli, dokter, jadwal, cuti, dan janji temu. Situs mengambil data lewat API gateway.">
  <form method="post" action="{{ route('admin.simrs.test') }}" class="inline">@csrf<button class="btn">Uji koneksi</button></form>
  <form method="post" action="{{ route('admin.simrs.sync') }}" class="inline">@csrf<button class="btn pri">Sinkronkan sekarang</button></form>
</x-admin-head>
@if($driver === 'fake')<div class="flash err">Mode data contoh aktif (SIMRS_DRIVER=fake). Isi SIMRS_DRIVER=http, SIMRS_BASE_URL, dan SIMRS_TOKEN di file .env untuk tersambung ke SIMRS asli.</div>@endif
<div class="conn">
  <div class="card"><small>Status</small><b><span class="pulse {{ $s['status'] === 'Gagal' ? 'bad' : '' }}"></span>{{ $s['status'] }}</b></div>
  <div class="card"><small>Sinkron terakhir</small><b>{{ $s['last_sync'] ? \Illuminate\Support\Carbon::parse($s['last_sync'])->diffForHumans() : 'Belum pernah' }}</b></div>
  <div class="card"><small>Mode pembaruan</small><b>{{ $s['mode'] === 'webhook' ? 'Webhook + cadangan '.$s['interval'].' menit' : 'Setiap '.$s['interval'].' menit' }}</b></div>
  <div class="card"><small>Sinkron berhasil</small><b>{{ $okCount }} dari {{ $allCount }}</b></div>
</div>
<div class="g2">
  <form class="card" method="post">@csrf
    <div class="card-h"><div><h2>Koneksi</h2><p>Alamat dan token disimpan di file .env, bukan di database.</p></div></div>
    <div class="card-b form">
      <div class="fld"><label>Driver dan alamat API</label><div class="ro">{{ $driver }} · {{ $baseUrl ?: 'belum diisi' }}</div></div>
      <div class="fld half"><label for="mode">Cara menerima perubahan</label><select id="mode" name="mode"><option value="webhook" @selected($s['mode'] === 'webhook')>Webhook dari SIMRS</option><option value="poll" @selected($s['mode'] === 'poll')>Pengecekan berkala</option></select></div>
      <div class="fld half"><label for="interval">Interval pengecekan</label><select id="interval" name="interval">@foreach([1, 2, 5, 10] as $n)<option value="{{ $n }}" @selected($s['interval'] == $n)>{{ $n }} menit</option>@endforeach</select><div class="hint">Juga cadangan saat webhook gagal. Butuh scheduler berjalan.</div></div>
      <div class="fld"><div class="tog-row"><div><b>Kirim janji temu ke SIMRS</b><small>Janji dari situs langsung dibuat di SIMRS dan mendapat kode booking.</small></div><label class="switch"><input type="checkbox" name="send_booking" value="1" @checked($s['send_booking'])><span></span></label></div></div>
      <div class="fld"><div class="tog-row"><div><b>Tetap terima janji saat SIMRS tidak terjangkau</b><small>Janji disimpan dan ditandai Gagal kirim, lalu bisa dikirim ulang dari menu Janji temu.</small></div><label class="switch"><input type="checkbox" name="fallback" value="1" @checked($s['fallback'])><span></span></label></div></div>
      <div class="fld"><label>Alamat webhook untuk vendor SIMRS</label><div class="ro">POST {{ route('simrs.webhook') }}<br>Header: X-Simrs-Secret: (nilai SIMRS_WEBHOOK_SECRET)</div></div>
    </div><div class="save-bar"><button class="btn pri">Simpan pengaturan</button></div></form>
  <div class="card"><div class="card-h"><div><h2>Pembagian data</h2><p>Satu data hanya diubah di satu tempat</p></div></div>
    <div class="table-scroll"><table class="tbl" style="min-width:0"><thead><tr><th>Data</th><th>Diubah di</th></tr></thead><tbody>
    @foreach([['Nama, gelar, dan poli dokter', 'SIMRS'], ['Jadwal praktik, kuota, dan slot', 'SIMRS'], ['Cuti dan libur dokter', 'SIMRS'], ['Janji temu dan statusnya', 'SIMRS (dibuat dari situs)'], ['Foto, warna, profil singkat dokter', 'Backoffice'], ['Deskripsi poli, urutan, unggulan', 'Backoffice'], ['Berita, slider, layanan, menu, chatbot, live chat', 'Backoffice']] as [$d, $w])
      <tr><td>{{ $d }}</td><td><span class="badge {{ str_starts_with($w, 'SIMRS') ? 'b-blue' : 'b-green' }}">{{ $w }}</span></td></tr>
    @endforeach
    </tbody></table></div></div>
</div>
<div class="card" style="margin-top:20px"><div class="card-h"><div><h2>Riwayat sinkronisasi</h2><p>50 kejadian terakhir</p></div></div>
  <div class="table-scroll"><table class="tbl"><thead><tr><th>Waktu</th><th>Hasil</th><th>Keterangan</th></tr></thead><tbody>
  @forelse($history as $h)<tr><td style="white-space:nowrap">{{ $h->created_at->translatedFormat('d M H.i') }}</td><td>{!! $h->ok ? '<span class="badge b-green">Berhasil</span>' : '<span class="badge b-red">Gagal</span>' !!}</td><td>{{ $h->message }}</td></tr>
  @empty<tr><td colspan="3"><div class="empty">Belum ada riwayat.</div></td></tr>@endforelse
  </tbody></table></div></div>
@endsection
