@extends('layouts.admin', ['current' => 'patients'])
@php use App\Services\Khanza\KhanzaPatients; use Carbon\Carbon; @endphp
@section('title', 'Kunjungan '.$v['info']['no_rawat'])
@section('content')
@php
  $i = $v['info'];
  $d = fn ($x, $f = 'd M Y') => $x && ! str_starts_with((string) $x, '0000') ? Carbon::parse($x)->translatedFormat($f) : '—';
  $t = fn ($x) => substr((string) $x, 0, 5);
  $has = fn ($x) => filled($x) && trim((string) $x) !== '-';
  $nav = collect([['tDx', 'Diagnosa', count($v['diagnosa']) + count($v['prosedur'])], ['tSoap', 'Pemeriksaan', count($v['soap'])], ['tTindakan', 'Tindakan', count($v['tindakan'])],
    ['tObat', 'Obat', count($v['obat'])], ['tLab', 'Laboratorium', count($v['lab'])], ['tRad', 'Radiologi', count($v['radiologi']) + count($v['radiologi_hasil'])],
    ['tKamar', 'Rawat inap', count($v['kamar'])], ['tOperasi', 'Operasi', count($v['operasi'])], ['tResume', 'Resume', count($v['resume'])]]);
@endphp
<x-admin-head module="patients" :title="$p['nm_pasien']" :desc="'Kunjungan '.$d($i['tgl_registrasi'], 'l, d F Y').' · No. rawat '.$i['no_rawat']">
  <a class="btn" href="{{ route('admin.patients.show', $p['no_rkm_medis']) }}">← Riwayat pasien</a>
  <button type="button" class="btn" onclick="window.print()"><svg class="i" viewBox="0 0 24 24"><path d="M6 9V3h12v6M6 18H4v-7h16v7h-2M8 14h8v7H8z"/></svg>Cetak</button>
</x-admin-head>
@include('admin.management._errors')

<div class="card" id="tInfo" style="margin-bottom:16px"><div class="card-b kv">
  <div><small>Pasien</small><b>{{ $p['nm_pasien'] }}</b><span class="sub">RM {{ $p['no_rkm_medis'] }} · {{ ($p['jk'] ?? '') === 'P' ? 'P' : 'L' }} · {{ KhanzaPatients::age($p['tgl_lahir'] ?? null, $i['tgl_registrasi']) }} saat berobat</span></div>
  <div><small>Tanggal & jam daftar</small><b>{{ $d($i['tgl_registrasi']) }} · {{ $t($i['jam_reg'] ?? '') }}</b></div>
  <div><small>Jenis</small><b>{{ ($i['status_lanjut'] ?? '') === 'Ranap' ? 'Rawat inap' : 'Rawat jalan' }}</b></div>
  <div><small>Poli</small><b>{{ $i['nm_poli'] ?? '—' }}</b></div>
  <div><small>Dokter</small><b>{{ $i['nm_dokter'] ?? '—' }}</b></div>
  <div><small>Penjamin</small><b>{{ $i['png_jawab'] ?? '—' }}</b></div>
  <div><small>Status</small><b>{{ $i['stts'] ?? '—' }}{{ filled($i['status_bayar'] ?? null) ? ' · '.$i['status_bayar'] : '' }}</b></div>
</div></div>

<nav class="pt-nav" aria-label="Bagian kunjungan">
  @foreach($nav as [$id, $label, $n])<a href="#{{ $id }}" class="{{ $n ? '' : 'dim' }}">{{ $label }} <span>{{ $n }}</span></a>@endforeach
</nav>

<section class="card pt-sec" id="tDx"><div class="card-h"><h2>Diagnosa & prosedur</h2></div><div class="card-b">
  @if($v['diagnosa'] || $v['prosedur'])
    <table class="tbl" style="min-width:0"><tbody>
      @foreach($v['diagnosa'] as $x)<tr><td style="width:110px"><b>{{ $x['kd_penyakit'] }}</b></td><td>{{ $x['nm_penyakit'] }}</td><td><span class="badge b-{{ (int) $x['prioritas'] === 1 ? 'blue' : 'grey' }}">{{ (int) $x['prioritas'] === 1 ? 'Utama' : 'Sekunder' }}</span> <span class="sub" style="display:inline">{{ $x['status'] }}</span></td></tr>@endforeach
      @foreach($v['prosedur'] as $x)<tr><td><b>{{ $x['kode'] }}</b></td><td>{{ $x['deskripsi_panjang'] }}</td><td><span class="badge b-grey">Prosedur (ICD-9)</span></td></tr>@endforeach
    </tbody></table>
  @else<div class="empty">Diagnosa belum diisi di SIMRS.</div>@endif
</div></section>

<section class="card pt-sec" id="tSoap"><div class="card-h"><h2>Pemeriksaan (SOAP) & tanda vital</h2></div><div class="card-b">
  @forelse($v['soap'] as $s)
    <article class="pt-soap">
      <header><b>{{ $d($s['tgl_perawatan'] ?? null, 'd M Y') }} · {{ $t($s['jam_rawat'] ?? '') }}</b><span>{{ $s['_jenis'] }}@if($s['_petugas']) · {{ $s['_petugas'] }}@endif</span></header>
      <div class="pt-vital">
        @foreach(['tensi' => 'Tensi', 'nadi' => 'Nadi', 'respirasi' => 'Napas', 'suhu_tubuh' => 'Suhu', 'spo2' => 'SpO2', 'gcs' => 'GCS', 'kesadaran' => 'Kesadaran', 'berat' => 'BB', 'tinggi' => 'TB', 'lingkar_perut' => 'LP'] as $k => $label)
          @if($has($s[$k] ?? null))<span><small>{{ $label }}</small>{{ $s[$k] }}</span>@endif
        @endforeach
      </div>
      <dl>
        @foreach(['keluhan' => 'S · Subjektif', 'pemeriksaan' => 'O · Objektif', 'penilaian' => 'A · Asesmen', 'rtl' => 'P · Rencana', 'instruksi' => 'Instruksi', 'evaluasi' => 'Evaluasi', 'alergi' => 'Alergi'] as $k => $label)
          @if($has($s[$k] ?? null))<dt>{{ $label }}</dt><dd>{!! nl2br(e($s[$k])) !!}</dd>@endif
        @endforeach
      </dl>
    </article>
  @empty<div class="empty">Tidak ada catatan pemeriksaan.</div>@endforelse
</div></section>

<section class="card pt-sec" id="tTindakan"><div class="card-h"><h2>Tindakan</h2></div>
  @if($v['tindakan'])<div class="table-scroll"><table class="tbl"><thead><tr><th>Waktu</th><th>Tindakan</th><th>Oleh</th></tr></thead><tbody>
    @foreach($v['tindakan'] as $x)<tr><td>{{ $d($x['tgl_perawatan'] ?? null, 'd M') }} {{ $t($x['jam_rawat'] ?? '') }}</td><td>{{ $x['nm_perawatan'] ?? $x['kd_jenis_prw'] }}</td><td>{{ $x['_oleh'] }}</td></tr>@endforeach
  </tbody></table></div>@else<div class="card-b"><div class="empty">Tidak ada tindakan tercatat.</div></div>@endif
</section>

<section class="card pt-sec" id="tObat"><div class="card-h"><h2>Obat & BMHP diberikan</h2></div>
  @if($v['obat'])<div class="table-scroll"><table class="tbl"><thead><tr><th>Waktu</th><th>Nama</th><th class="n">Jumlah</th><th>Aturan pakai</th></tr></thead><tbody>
    @foreach($v['obat'] as $x)<tr><td>{{ $d($x['tgl_perawatan'] ?? null, 'd M') }} {{ $t($x['jam'] ?? '') }}</td><td>{{ $x['nama_brng'] ?? $x['kode_brng'] }}</td><td class="n">{{ rtrim(rtrim(number_format((float) $x['jml'], 2, ',', '.'), '0'), ',') }} {{ $x['kode_sat'] }}</td><td>{{ $x['aturan'] ?? '' }}</td></tr>@endforeach
  </tbody></table></div>@else<div class="card-b"><div class="empty">Tidak ada pemberian obat tercatat.</div></div>@endif
</section>

<section class="card pt-sec" id="tLab"><div class="card-h"><h2>Hasil laboratorium</h2></div>
  @if($v['lab'])<div class="table-scroll"><table class="tbl"><thead><tr><th>Pemeriksaan</th><th class="n">Hasil</th><th>Satuan</th><th>Nilai rujukan</th><th>Ket.</th></tr></thead><tbody>
    @php $grp = null; @endphp
    @foreach($v['lab'] as $x)
      @if(($x['nm_perawatan'] ?? '').$x['tgl_periksa'] !== $grp)@php $grp = ($x['nm_perawatan'] ?? '').$x['tgl_periksa']; @endphp<tr class="stk-grp"><td colspan="5">{{ $x['nm_perawatan'] }} · {{ $d($x['tgl_periksa'], 'd M Y') }} {{ $t($x['jam'] ?? '') }}</td></tr>@endif
      <tr><td>{{ $x['pemeriksaan'] }}</td><td class="n"><b class="{{ in_array(strtoupper((string) ($x['keterangan'] ?? '')), ['H', 'L', 'T', 'R', '*', 'TINGGI', 'RENDAH'], true) ? 'pt-flag' : '' }}">{{ $x['nilai'] }}</b></td><td>{{ $x['satuan'] }}</td><td>{{ $x['nilai_rujukan'] }}</td><td>{{ $x['keterangan'] }}</td></tr>
    @endforeach
  </tbody></table></div>@else<div class="card-b"><div class="empty">Tidak ada pemeriksaan laboratorium.</div></div>@endif
</section>

<section class="card pt-sec" id="tRad"><div class="card-h"><h2>Radiologi</h2></div><div class="card-b">
  @forelse($v['radiologi'] as $x)<p style="margin:0 0 6px"><b>{{ $x['nm_perawatan'] }}</b> · {{ $d($x['tgl_periksa']) }} {{ $t($x['jam'] ?? '') }}</p>@empty @if(! $v['radiologi_hasil'])<div class="empty">Tidak ada pemeriksaan radiologi.</div>@endif @endforelse
  @foreach($v['radiologi_hasil'] as $x)<div class="pt-text"><small>Hasil · {{ $d($x['tgl_periksa'] ?? null) }} {{ $t($x['jam'] ?? '') }}</small>{!! nl2br(e($x['hasil'] ?? '')) !!}</div>@endforeach
</div></section>

<section class="card pt-sec" id="tKamar"><div class="card-h"><h2>Rawat inap</h2></div>
  @if($v['kamar'])<div class="table-scroll"><table class="tbl"><thead><tr><th>Ruang / kamar</th><th>Masuk</th><th>Keluar</th><th>Lama</th><th>Diagnosa awal / akhir</th><th>Status pulang</th></tr></thead><tbody>
    @foreach($v['kamar'] as $x)<tr><td>{{ $x['nm_bangsal'] }}<span class="sub">{{ $x['kd_kamar'] }}</span></td><td>{{ $d($x['tgl_masuk']) }} {{ $t($x['jam_masuk'] ?? '') }}</td><td>{{ $d($x['tgl_keluar']) }} {{ $has($x['tgl_keluar'] ?? null) && ! str_starts_with((string) $x['tgl_keluar'], '0000') ? $t($x['jam_keluar'] ?? '') : '' }}</td><td>{{ $x['lama'] }} hr</td><td>{{ $x['diagnosa_awal'] }}<span class="sub">{{ $x['diagnosa_akhir'] }}</span></td><td>{{ $x['stts_pulang'] }}</td></tr>@endforeach
  </tbody></table></div>@else<div class="card-b"><div class="empty">Bukan kunjungan rawat inap.</div></div>@endif
</section>

<section class="card pt-sec" id="tOperasi"><div class="card-h"><h2>Laporan operasi</h2></div><div class="card-b">
  @forelse($v['operasi'] as $x)
    <div class="pt-text"><small>{{ $d($x['tanggal'] ?? null, 'd M Y H.i') }}</small>
      <dl class="pt-dl">@foreach(collect($x)->except(['no_rawat', 'tanggal']) as $k => $val)@if($has($val))<dt>{{ ucfirst(str_replace('_', ' ', $k)) }}</dt><dd>{!! nl2br(e($val)) !!}</dd>@endif @endforeach</dl></div>
  @empty<div class="empty">Tidak ada laporan operasi.</div>@endforelse
</div></section>

<section class="card pt-sec" id="tResume"><div class="card-h"><h2>Resume medis</h2></div><div class="card-b">
  @forelse($v['resume'] as $label => $x)
    <div class="pt-text"><small>{{ match ($label) { 'resume_pasien' => 'Resume rawat jalan', default => 'Resume rawat inap' } }}</small>
      <dl class="pt-dl">@foreach(collect($x)->except(['no_rawat']) as $k => $val)@if($has($val))<dt>{{ ucfirst(str_replace('_', ' ', $k)) }}</dt><dd>{!! nl2br(e($val)) !!}</dd>@endif @endforeach</dl></div>
  @empty<div class="empty">Resume belum dibuat.</div>@endforelse
</div></section>
@include('admin.tours.patients-visit')
@endsection
