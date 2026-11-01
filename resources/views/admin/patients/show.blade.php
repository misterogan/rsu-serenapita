@extends('layouts.admin', ['current' => 'patients'])
@php use App\Services\Khanza\KhanzaPatients; use Carbon\Carbon; @endphp
@section('title', $p['nm_pasien'])
@section('content')
@php
  $d = fn ($v, $f = 'd M Y') => $v && ! str_starts_with((string) $v, '0000') ? Carbon::parse($v)->translatedFormat($f) : '—';
  $v_ = fn ($k) => filled($p[$k] ?? null) && ($p[$k] ?? '') !== '-' ? $p[$k] : '—';
  $alamat = collect([$p['alamat'] ?? null, $p['_kelurahan'] ? 'Kel. '.$p['_kelurahan'] : null, $p['_kecamatan'] ? 'Kec. '.$p['_kecamatan'] : null, $p['_kabupaten']])->filter(fn ($x) => filled($x) && $x !== '-')->implode(', ');
  $ranap = collect($visits)->where('status_lanjut', 'Ranap')->count();
  $valid = collect($visits)->where('stts', '<>', 'Batal');
  $vt = $clinical['vital'];
@endphp
<x-admin-head module="patients" :title="$p['nm_pasien']" :desc="'No. RM '.$p['no_rkm_medis'].' · '.(($p['jk'] ?? '') === 'P' ? 'Perempuan' : 'Laki-laki').' · '.KhanzaPatients::age($p['tgl_lahir'] ?? null)">
  <a class="btn" href="{{ route('admin.patients') }}">← Cari pasien lain</a>
  <button type="button" class="btn" onclick="window.print()"><svg class="i" viewBox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4v-7h16v7h-2M8 14h8v7H8z"/></svg>Cetak</button>
</x-admin-head>
@include('admin.management._errors')

@if($clinical['alergi'])
  <div class="flash err pt-alergi" id="tAlergi"><b>Riwayat alergi:</b> {{ implode(' · ', $clinical['alergi']) }}</div>
@endif

<div class="g3" style="margin-bottom:20px">
  <div class="card" id="tIdentity"><div class="card-h"><h2>Identitas pasien</h2></div><div class="card-b kv">
    <div><small>No. rekam medis</small><b>{{ $p['no_rkm_medis'] }}</b></div>
    <div><small>NIK</small><b>{{ $v_('no_ktp') }}</b></div>
    <div><small>Tempat, tanggal lahir</small><b>{{ $v_('tmp_lahir') }}, {{ $d($p['tgl_lahir'] ?? null, 'd F Y') }}</b></div>
    <div><small>Umur</small><b>{{ KhanzaPatients::age($p['tgl_lahir'] ?? null) }}</b></div>
    <div><small>Jenis kelamin</small><b>{{ ($p['jk'] ?? '') === 'P' ? 'Perempuan' : 'Laki-laki' }}</b></div>
    <div><small>Golongan darah</small><b>{{ $v_('gol_darah') }}</b></div>
    <div><small>Agama</small><b>{{ $v_('agama') }}</b></div>
    <div><small>Status nikah</small><b>{{ $v_('stts_nikah') }}</b></div>
    <div><small>Pendidikan</small><b>{{ $v_('pnd') }}</b></div>
    <div><small>Pekerjaan</small><b>{{ $v_('pekerjaan') }}</b></div>
    <div class="wide"><small>Alamat</small><b>{{ $alamat ?: '—' }}</b></div>
    <div><small>No. telepon</small><b>@if(filled($p['no_tlp'] ?? null))<a href="tel:{{ $p['no_tlp'] }}">{{ $p['no_tlp'] }}</a>@else — @endif</b></div>
    <div><small>Nama ibu kandung</small><b>{{ $v_('nm_ibu') }}</b></div>
    <div><small>Penanggung jawab</small><b>{{ $v_('namakeluarga') }}@if(filled($p['keluarga'] ?? null)) ({{ strtolower($p['keluarga']) }})@endif</b></div>
    <div><small>Cara bayar</small><b>{{ $p['_penjamin'] ?? '—' }}</b></div>
    <div><small>No. peserta / BPJS</small><b>{{ $v_('no_peserta') }}</b></div>
    <div><small>Terdaftar sejak</small><b>{{ $d($p['tgl_daftar'] ?? null) }}</b></div>
  </div></div>

  <div style="display:flex;flex-direction:column;gap:20px">
    <div class="card" id="tSummary"><div class="card-h"><h2>Ringkasan</h2></div><div class="card-b kv sm">
      <div><small>Total kunjungan</small><b>{{ $valid->count() }}</b></div>
      <div><small>Rawat inap</small><b>{{ $ranap }}×</b></div>
      <div><small>Pertama datang</small><b>{{ $d($valid->last()['tgl_registrasi'] ?? null) }}</b></div>
      <div><small>Terakhir datang</small><b>{{ $d($valid->first()['tgl_registrasi'] ?? null) }}</b></div>
    </div></div>
    <div class="card" id="tVital"><div class="card-h"><div><h2>Tanda vital terakhir</h2>@if($vt)<p>{{ $d($vt['tgl_perawatan'] ?? null) }} pukul {{ substr((string) ($vt['jam_rawat'] ?? ''), 0, 5) }}</p>@endif</div></div><div class="card-b kv sm">
      @if($vt)
        @foreach(['tensi' => ['Tensi', 'mmHg'], 'nadi' => ['Nadi', '/mnt'], 'respirasi' => ['Napas', '/mnt'], 'suhu_tubuh' => ['Suhu', '°C'], 'spo2' => ['SpO2', '%'], 'berat' => ['Berat', 'kg'], 'tinggi' => ['Tinggi', 'cm'], 'gcs' => ['GCS', '']] as $k => [$label, $unit])
          @if(filled($vt[$k] ?? null) && $vt[$k] !== '-')<div><small>{{ $label }}</small><b>{{ $vt[$k] }} {{ $unit }}</b></div>@endif
        @endforeach
      @else<div class="empty" style="padding:10px 0">Belum ada pemeriksaan rawat jalan.</div>@endif
    </div></div>
    @if($clinical['sering'])
    <div class="card"><div class="card-h"><h2>Diagnosa tersering</h2></div><div class="card-b"><ul class="feed">
      @foreach($clinical['sering'] as $dx => $n)<li><span style="flex:1">{{ $dx }}</span><b>{{ $n }}×</b></li>@endforeach
    </ul></div></div>
    @endif
  </div>
</div>

<div class="card" id="tVisits">
  <div class="list-tools">
    <input type="search" id="vsQ" placeholder="Cari poli, dokter, diagnosa…" aria-label="Cari kunjungan" autocomplete="off">
    <select id="vsT" aria-label="Jenis"><option value="">Semua jenis</option><option value="Ralan">Rawat jalan</option><option value="Ranap">Rawat inap</option></select>
    <select id="vsY" aria-label="Tahun"><option value="">Semua tahun</option>@foreach(collect($visits)->map(fn ($v) => substr((string) $v['tgl_registrasi'], 0, 4))->unique() as $y)<option>{{ $y }}</option>@endforeach</select>
    <span class="count" id="vsCount">{{ count($visits) }} kunjungan</span>
  </div>
  <div class="table-scroll"><table class="tbl">
    <thead><tr><th>Tanggal</th><th>Jenis / ruang</th><th>Poli / dokter</th><th>Diagnosa</th><th>Penjamin</th><th>Status</th></tr></thead>
    <tbody>
    @forelse($visits as $v)
      <tr class="pt-link vs-row" data-href="{{ route('admin.patients.visit', [$p['no_rkm_medis'], 'rawat' => $v['no_rawat']]) }}" data-type="{{ $v['status_lanjut'] }}" data-year="{{ substr((string) $v['tgl_registrasi'], 0, 4) }}" data-text="{{ mb_strtolower($v['nm_poli'].' '.$v['nm_dokter'].' '.implode(' ', $v['diagnosa']).' '.$v['bangsal']) }}">
        <td><a class="main" href="{{ route('admin.patients.visit', [$p['no_rkm_medis'], 'rawat' => $v['no_rawat']]) }}">{{ $d($v['tgl_registrasi'], 'd M Y') }}</a><span class="sub">{{ $v['no_rawat'] }}</span></td>
        <td><span class="badge b-{{ $v['status_lanjut'] === 'Ranap' ? 'amber' : 'blue' }}">{{ $v['status_lanjut'] === 'Ranap' ? 'Rawat inap' : 'Rawat jalan' }}</span>
          @if($v['bangsal'])<span class="sub">{{ $v['bangsal'] }}@if($v['keluar'] && ! str_starts_with((string) $v['keluar'], '0000')) · pulang {{ $d($v['keluar'], 'd M') }}@endif</span>@endif</td>
        <td>{{ $v['nm_poli'] }}<span class="sub">{{ $v['nm_dokter'] }}</span></td>
        <td style="max-width:320px">{{ $v['diagnosa'][0] ?? '—' }}@if(count($v['diagnosa']) > 1)<span class="sub">+{{ count($v['diagnosa']) - 1 }} diagnosa lain</span>@endif</td>
        <td>{{ $v['png_jawab'] }}</td>
        <td><span class="badge b-{{ $v['stts'] === 'Batal' ? 'red' : ($v['stts'] === 'Sudah' ? 'green' : 'grey') }}">{{ $v['stts'] }}</span></td>
      </tr>
    @empty
      <tr><td colspan="6"><div class="empty">Belum ada riwayat kunjungan.</div></td></tr>
    @endforelse
    </tbody>
  </table></div>
</div>
@include('admin.tours.patients-show')
@endsection
@push('scripts')
<script>
(function () {
  document.querySelectorAll(".pt-link").forEach(tr => tr.addEventListener("click", e => { if (!e.target.closest("a")) location.href = tr.dataset.href; }));
  const $ = s => document.querySelector(s), rows = [...document.querySelectorAll(".vs-row")], q = $("#vsQ"), t = $("#vsT"), y = $("#vsY");
  const apply = () => { const w = q.value.toLowerCase().trim(); let n = 0;
    rows.forEach(r => { const ok = (!t.value || r.dataset.type === t.value) && (!y.value || r.dataset.year === y.value) && (!w || r.dataset.text.includes(w)); r.hidden = !ok; n += ok; });
    $("#vsCount").textContent = n + " kunjungan"; };
  [q, t, y].forEach(el => el.addEventListener("input", apply));
})();
</script>
@endpush
