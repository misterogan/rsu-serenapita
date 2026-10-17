@use('App\Support\Fmt', 'F')
@include('admin.management._errors')
@php
  $alerts = [];
  if ($ops) {
    $s = $summary; $p = $prev; $lyS = $ly;
    $bor = $ind['bor'] ?? null; $nowBed = F::pct($now['terisi'] ?? 0, $now['tempat_tidur'] ?? 0);
    $waitPct = F::pct($wait['tepat'] ?? 0, $wait['pasien'] ?? 0);
    $readmPct = F::pct($readm['dirawat_ulang'] ?? 0, $readm['pasien_pulang'] ?? 0);
    $expSoon = collect($expiring)->where('sisa_hari', '<=', 30)->count(); $expired = collect($expiring)->where('sisa_hari', '<', 0)->count();
    if ($nowBed >= 90) $alerts[] = ['hot', 'Tempat tidur terisi '.F::pf($now['terisi'], $now['tempat_tidur']).'% saat ini. Pertimbangkan percepatan pemulangan pasien.'];
    if (($wait['pasien'] ?? 0) && $waitPct < 80) $alerts[] = ['warn', 'Hanya '.F::n($waitPct, 1).'% pasien rawat jalan diperiksa dalam 60 menit setelah mendaftar (target ≥ 80%).'];
    if (($readm['pasien_pulang'] ?? 0) && $readmPct > 5) $alerts[] = ['warn', 'Pasien dirawat ulang dalam 30 hari: '.F::n($readmPct, 1).'% (batas wajar ≤ 5%).'];
    if (count($lowStock)) $alerts[] = ['warn', count($lowStock).' obat di bawah stok minimal. Lihat tab Penunjang & farmasi.'];
    if ($expired) $alerts[] = ['hot', $expired.' batch obat sudah kedaluwarsa tetapi masih ada sisa stok.'];
    elseif ($expSoon) $alerts[] = ['warn', $expSoon.' batch obat kedaluwarsa dalam 30 hari.'];
  }
  if ($fin) {
    $pay = collect($payments)->sum('total'); $rec = collect($receivables)->sum('total_piutang');
    $lyRev = collect($lyPayments)->sum('total') + collect($lyReceivables)->sum('total_piutang');
    $over90 = collect($aging)->sum('lebih_90'); $overdue = collect($payables)->sum('lewat_jatuh_tempo');
    if ($over90 > 0) $alerts[] = ['hot', 'Piutang lebih dari 90 hari: '.F::rp($over90).'. Lihat tab Keuangan.'];
    if ($overdue > 0) $alerts[] = ['warn', 'Hutang supplier lewat jatuh tempo: '.F::rp($overdue).'.'];
  }
  $tg = $targets; $mult = $days / 30.4;
@endphp

@if($alerts)
<div class="mg-alerts">@foreach($alerts as [$lvl, $msg])<div class="mg-alert {{ $lvl }}">{{ $msg }}</div>@endforeach</div>
@else
<div class="mg-alert ok">Tidak ada peringatan untuk periode ini.</div>
@endif

@if($ops)
  <div class="mg-kpis">
    @foreach([
      ['Kunjungan', $s['kunjungan'] ?? 0, $p['kunjungan'] ?? 0, $lyS['kunjungan'] ?? 0, F::n($s['pasien_unik'] ?? 0).' pasien berbeda'],
      ['Rawat jalan', $s['rawat_jalan'] ?? 0, $p['rawat_jalan'] ?? 0, $lyS['rawat_jalan'] ?? 0, F::n($s['pasien_baru'] ?? 0).' pasien baru'],
      ['IGD', $s['igd'] ?? 0, $p['igd'] ?? 0, $lyS['igd'] ?? 0, F::n($s['lanjut_ranap'] ?? 0).' lanjut rawat inap'],
      ['Pasien BPJS', $s['bpjs'] ?? 0, null, $lyS['bpjs'] ?? 0, F::pf($s['bpjs'] ?? 0, $s['kunjungan'] ?? 0).'% dari kunjungan'],
      ['Dirawat saat ini', $now['dirawat'] ?? 0, null, null, 'TT terisi '.F::n($now['terisi'] ?? 0).' dari '.F::n($now['tempat_tidur'] ?? 0)],
      ['Batal daftar', $s['batal'] ?? 0, null, null, F::pf($s['batal'] ?? 0, ($s['kunjungan'] ?? 0) + ($s['batal'] ?? 0)).'% dari pendaftaran'],
    ] as [$label, $val, $old, $lyv, $sub])
    <div class="mg-kpi"><small>{{ $label }}</small><b>{{ F::n($val) }}</b><span>{{ $sub }}</span>
      @if($old !== null && ($d = F::delta($val, $old)) !== null)<em class="{{ $d >= 0 ? 'up' : 'down' }}">{{ $d >= 0 ? '▲' : '▼' }} {{ F::n(abs($d), 1) }}% vs periode sebelumnya</em>@endif
      @if($lyv !== null && ($d = F::delta($val, $lyv)) !== null)<em class="{{ $d >= 0 ? 'up' : 'down' }}">{{ $d >= 0 ? '▲' : '▼' }} {{ F::n(abs($d), 1) }}% vs tahun lalu</em>@endif
    </div>
    @endforeach
  </div>

  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Indikator mutu utama'])
    <div class="mg-ind">
      @foreach([
        ['Tunggu ≤ 60 menit', ($wait['pasien'] ?? 0) ? F::n($waitPct, 1).'%' : '—', 'Pasien rawat jalan diperiksa ≤ 60 menit', 'Target ≥ 80%', ($wait['pasien'] ?? 0) ? $waitPct >= 80 : null],
        ['Rata-rata tunggu', F::n($wait['rata_menit'] ?? null).' menit', 'Daftar sampai diperiksa', 'Target ≤ 60 menit', isset($wait['rata_menit']) ? $wait['rata_menit'] <= 60 : null],
        ['BOR', $bor === null ? '—' : F::n($bor, 1).'%', 'Pemakaian tempat tidur', 'Ideal 60–85%', $bor === null ? null : ($bor >= 60 && $bor <= 85)],
        ['ALOS', ($ind['alos'] ?? null) === null ? '—' : F::n($ind['alos'], 1).' hari', 'Rata-rata lama dirawat', 'Ideal 6–9 hari', ($ind['alos'] ?? null) === null ? null : ($ind['alos'] >= 6 && $ind['alos'] <= 9)],
        ['Dirawat ulang', ($readm['pasien_pulang'] ?? 0) ? F::n($readmPct, 1).'%' : '—', 'Kembali rawat inap ≤ 30 hari', 'Batas ≤ 5%', ($readm['pasien_pulang'] ?? 0) ? $readmPct <= 5 : null],
        ['NDR', ($ind['ndr'] ?? null) === null ? '—' : F::n($ind['ndr'], 1).'‰', 'Kematian ≥ 48 jam', 'Ideal ≤ 25‰', ($ind['ndr'] ?? null) === null ? null : $ind['ndr'] <= 25],
      ] as [$code, $val, $desc, $ideal, $ok])
      <div class="ind {{ $ok === null ? '' : ($ok ? 'ok' : 'warn') }}"><small>{{ $code }}</small><b>{{ $val }}</b><span>{{ $desc }}</span><em>{{ $ideal }}</em></div>
      @endforeach
    </div>
  </div>
@endif

@if($fin)
  <div class="mg-kpis">
    <div class="mg-kpi"><small>Pembayaran diterima</small><b class="sm">{{ F::rps($pay) }}</b><span>tunai dan non-tunai</span></div>
    <div class="mg-kpi"><small>Piutang baru</small><b class="sm">{{ F::rps($rec) }}</b><span>BPJS dan asuransi</span></div>
    <div class="mg-kpi"><small>Total pendapatan</small><b class="sm">{{ F::rps($pay + $rec) }}</b><span>pembayaran + piutang</span>
      @if(($d = F::delta($pay + $rec, $lyRev)) !== null)<em class="{{ $d >= 0 ? 'up' : 'down' }}">{{ $d >= 0 ? '▲' : '▼' }} {{ F::n(abs($d), 1) }}% vs tahun lalu</em>@endif</div>
    <div class="mg-kpi"><small>Sisa piutang</small><b class="sm">{{ F::rps(collect($aging)->sum('total_sisa')) }}</b><span>{{ F::rps($over90) }} lebih dari 90 hari</span></div>
    <div class="mg-kpi"><small>Hutang supplier</small><b class="sm">{{ F::rps(collect($payables)->sum('sisa')) }}</b><span>{{ F::rps($overdue) }} lewat jatuh tempo</span></div>
  </div>
@endif

@php
  $tRows = [];
  if ($ops && $tg['kunjungan'] > 0) $tRows[] = ['Kunjungan', F::n($s['kunjungan'] ?? 0), F::n($tg['kunjungan'] * $mult), F::pct($s['kunjungan'] ?? 0, $tg['kunjungan'] * $mult)];
  if ($fin && $tg['pendapatan'] > 0) $tRows[] = ['Pendapatan', F::rps($pay + $rec), F::rps($tg['pendapatan'] * $mult), F::pct($pay + $rec, $tg['pendapatan'] * $mult)];
  if ($ops && $tg['bor'] > 0) $tRows[] = ['BOR', $bor === null ? '—' : F::n($bor, 1).'%', F::n($tg['bor'], 1).'%', $bor === null ? 0 : F::pct($bor, $tg['bor'])];
@endphp
@if($tRows || $canTarget)
<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Target dan realisasi', 'note' => 'Target bulanan disesuaikan dengan panjang periode ('.$days.' hari).'])
  @if($tRows)
  <table class="mg-tbl"><thead><tr><th>Indikator</th><th class="r">Realisasi</th><th class="r">Target periode</th><th style="width:40%">Pencapaian</th></tr></thead><tbody>
  @foreach($tRows as [$nm, $real, $target, $pc])<tr><td><b>{{ $nm }}</b></td><td class="r">{{ $real }}</td><td class="r">{{ $target }}</td><td><div class="mg-prog"><i class="{{ $pc >= 100 ? 'ok' : ($pc >= 80 ? '' : 'hot') }}" style="width:{{ min(100, $pc) }}%"></i><span>{{ F::n($pc, 1) }}%</span></div></td></tr>@endforeach
  </tbody></table>
  @else<p class="mg-empty">Belum ada target. Isi target bulanan di bawah.</p>@endif
  @if($canTarget)
  <details class="mg-target-form"><summary>Ubah target bulanan</summary>
    <form method="post" action="{{ route('admin.management.targets') }}" class="mg-custom" style="margin-top:10px">@csrf
      <label>Kunjungan per bulan <input type="number" name="kunjungan" min="0" value="{{ $tg['kunjungan'] ?: '' }}"></label>
      <label>Pendapatan per bulan (Rp) <input type="number" name="pendapatan" min="0" value="{{ $tg['pendapatan'] ?: '' }}"></label>
      <label>BOR (%) <input type="number" name="bor" min="0" max="100" step="0.1" value="{{ $tg['bor'] ?: '' }}"></label>
      <button class="btn sm pri">Simpan target</button>
    </form>
  </details>
  @endif
</div>
@endif

@if($ops)
<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Kunjungan 12 bulan terakhir', 'csv' => 'tren-bulanan'])
  @include('admin.management._bars', ['rows' => $months, 'label' => 'bulan', 'series' => [['kunjungan', 'Kunjungan', 'b1']]])
</div>
@endif
@if($fin)
<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Pendapatan 12 bulan terakhir', 'csv' => 'tren-pendapatan'])
  @include('admin.management._bars', ['rows' => $revMonths, 'label' => 'bulan', 'money' => true, 'series' => [['pembayaran', 'Pembayaran', 'b1'], ['piutang', 'Piutang baru', 'b2']]])
</div>
@endif
