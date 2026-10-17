@use('App\Support\Fmt', 'F')
@include('admin.management._errors')

<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Tren kunjungan harian', 'csv' => 'tren'])
  @if(count($trend) > 1)
    @php
      $W = 900; $H = 220; $pad = 34; $max = max(1, collect($trend)->max('total'));
      $pts = fn ($key) => collect($trend)->values()->map(fn ($r, $i) => round($pad + $i * ($W - 2 * $pad) / max(1, count($trend) - 1), 1).','.round($H - 24 - ($r[$key] / $max) * ($H - 50), 1))->implode(' ');
      $step = max(1, (int) ceil(count($trend) / 10));
    @endphp
    <svg class="mg-trend" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik tren kunjungan harian">
      @foreach([0, .5, 1] as $g)<line x1="{{ $pad }}" x2="{{ $W - $pad }}" y1="{{ $H - 24 - $g * ($H - 50) }}" y2="{{ $H - 24 - $g * ($H - 50) }}" class="grid"/><text x="{{ $pad - 6 }}" y="{{ $H - 20 - $g * ($H - 50) }}" class="ax" text-anchor="end">{{ round($max * $g) }}</text>@endforeach
      <polyline points="{{ $pts('total') }}" class="l-total"/><polyline points="{{ $pts('rawat_jalan') }}" class="l-ralan"/><polyline points="{{ $pts('igd') }}" class="l-igd"/>
      @foreach(collect($trend)->values() as $i => $r)@if($i % $step === 0)<text x="{{ round($pad + $i * ($W - 2 * $pad) / max(1, count($trend) - 1), 1) }}" y="{{ $H - 6 }}" class="ax" text-anchor="middle">{{ \Illuminate\Support\Carbon::parse($r['tanggal'])->format('d/m') }}</text>@endif @endforeach
    </svg>
    <div class="mg-legend"><span class="k-total">Total</span><span class="k-ralan">Rawat jalan</span><span class="k-igd">IGD</span></div>
  @else
    <p class="mg-empty">Pilih periode lebih dari satu hari untuk melihat tren.</p>
  @endif
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Kunjungan per poliklinik', 'csv' => 'poli', 'note' => 'Klik nama poli untuk melihat dokter dan diagnosa terbanyak.'])
    @php $mx = max(1, collect($clinics)->max('kunjungan')); @endphp
    <table class="mg-tbl"><thead><tr><th>Poliklinik</th><th class="r">Kunjungan</th><th class="r">Baru</th><th class="r">BPJS</th><th class="r">Batal</th></tr></thead><tbody>
    @forelse($clinics as $c)<tr><td><a href="#" class="mg-drill" data-poli-url="{{ route('admin.management.clinic', [$c['kode']] + $pq + ['nama' => $c['poliklinik']]) }}">{{ $c['poliklinik'] }}</a><span class="bar" style="width:{{ F::pct($c['kunjungan'], $mx) }}%"></span></td><td class="r"><b>{{ F::n($c['kunjungan']) }}</b></td><td class="r">{{ F::n($c['baru']) }}</td><td class="r">{{ F::n($c['bpjs']) }}</td><td class="r">{{ F::n($c['batal']) }}</td></tr>
    @empty<tr><td colspan="5" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Cara bayar', 'csv' => 'penjab'])
    @php $tot = max(1, collect($payers)->sum('kunjungan')); @endphp
    @forelse($payers as $c)<div class="mg-row"><span>{{ $c['cara_bayar'] }}</span><b>{{ F::n($c['kunjungan']) }} <small>({{ F::pf($c['kunjungan'], $tot) }}%)</small></b><i class="bar" style="width:{{ F::pct($c['kunjungan'], $tot) }}%"></i></div>
    @empty<p class="mg-empty">Tidak ada data.</p>@endforelse
    <div class="mg-mini" style="margin-top:16px">
      <div><small>Tunggu ≤ 60 menit</small><b>{{ F::pf($wait['tepat'] ?? 0, $wait['pasien'] ?? 0) }}%</b><span>{{ F::n($wait['tepat'] ?? 0) }} dari {{ F::n($wait['pasien'] ?? 0) }} pasien rawat jalan</span></div>
      <div><small>Rata-rata tunggu</small><b>{{ F::n($wait['rata_menit'] ?? null) }} menit</b><span>daftar sampai diperiksa</span></div>
    </div>
  </div>
</div>

<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Kinerja dokter rawat jalan', 'csv' => 'dokter', 'note' => '<b>Waktu tunggu</b>: jam daftar sampai pemeriksaan pertama dicatat. <b>Lama layanan</b>: dipanggil dokter sampai selesai di poli, dari antrean online (task 4 sampai 5).'])
  <div class="table-scroll"><table class="mg-tbl"><thead><tr><th>Dokter</th><th>Poliklinik</th><th class="r">Pasien</th><th class="r">Rata-rata tunggu</th><th class="r">Rata-rata layanan</th><th class="r">Tercatat antrol</th></tr></thead><tbody>
  @forelse($doctors as $d)<tr><td><b>{{ $d['dokter'] }}</b></td><td>{{ $d['poliklinik'] }}</td><td class="r">{{ F::n($d['pasien']) }}</td>
    <td class="r"><span class="tag {{ ($d['rata_tunggu_menit'] ?? 0) > 60 ? 'warn' : 'ok' }}">{{ F::n($d['rata_tunggu_menit']) }} menit</span></td>
    <td class="r">{{ $d['rata_layanan_menit'] !== null ? F::n($d['rata_layanan_menit']).' menit' : '—' }}</td><td class="r">{{ F::n($d['tercatat_antrol']) }}</td></tr>
  @empty<tr><td colspan="6" class="mg-empty">Tidak ada data.</td></tr>@endforelse
  </tbody></table></div>
</div>

<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Ketepatan jam mulai praktik dokter', 'csv' => 'ketepatan-dokter', 'note' => 'Jam pemeriksaan pertama tiap hari dibandingkan jam mulai di jadwal praktik. Dianggap tepat waktu bila mulai paling lambat 15 menit setelah jadwal. Pemeriksaan pertama bisa dicatat oleh perawat, jadi angka ini perkiraan.'])
  <div class="table-scroll"><table class="mg-tbl"><thead><tr><th>Dokter</th><th class="r">Hari praktik</th><th class="r">Rata-rata mulai</th><th class="r">Tepat waktu</th><th style="width:28%"></th></tr></thead><tbody>
  @forelse($punctuality as $d)@php $pc = F::pct($d['tepat_waktu'], $d['hari_praktik']); @endphp<tr><td><b>{{ $d['dokter'] }}</b></td><td class="r">{{ F::n($d['hari_praktik']) }}</td>
    <td class="r">{{ $d['rata_selisih_menit'] > 0 ? F::n($d['rata_selisih_menit']).' menit terlambat' : F::n(abs($d['rata_selisih_menit'])).' menit lebih awal' }}</td>
    <td class="r"><b>{{ F::n($pc, 1) }}%</b></td><td><div class="mg-prog"><i class="{{ $pc >= 80 ? 'ok' : ($pc >= 50 ? '' : 'hot') }}" style="width:{{ $pc }}%"></i></div></td></tr>
  @empty<tr><td colspan="5" class="mg-empty">Tidak ada data. Pastikan jadwal dokter di Khanza sudah diisi.</td></tr>@endforelse
  </tbody></table></div>
</div>

<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Jam sibuk pendaftaran', 'csv' => 'jam-sibuk', 'note' => 'Jumlah pendaftaran (rawat jalan dan IGD) per jam dan hari selama periode. Semakin gelap, semakin ramai.'])
  @php
    $cells = []; foreach ($busy as $b) { $cells[(int) $b['hari']][(int) $b['jam']] = (int) $b['total']; }
    $hours = collect($busy)->pluck('jam')->map(fn ($h) => (int) $h); $h0 = min(6, $hours->min() ?? 6); $h1 = max(21, $hours->max() ?? 21);
    $mxc = max(1, collect($busy)->max('total')); $daysName = [2 => 'Senin', 3 => 'Selasa', 4 => 'Rabu', 5 => 'Kamis', 6 => 'Jumat', 7 => 'Sabtu', 1 => 'Minggu'];
  @endphp
  <div class="table-scroll"><table class="mg-heat"><thead><tr><th></th>@for($h = $h0; $h <= $h1; $h++)<th>{{ sprintf('%02d', $h) }}</th>@endfor</tr></thead><tbody>
  @foreach($daysName as $dn => $nm)<tr><th>{{ $nm }}</th>@for($h = $h0; $h <= $h1; $h++)@php $v = $cells[$dn][$h] ?? 0; @endphp<td style="background:rgba(31,111,178,{{ $v ? round(0.08 + 0.92 * $v / $mxc, 2) : 0 }});{{ $v / $mxc > .55 ? 'color:#fff' : '' }}" title="{{ $nm }} {{ sprintf('%02d.00', $h) }}: {{ $v }} pendaftaran">{{ $v ?: '' }}</td>@endfor</tr>@endforeach
  </tbody></table></div>
</div>

<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Antrean online (Mobile JKN)', 'csv' => 'antrol'])
  @php $aTot = max(1, collect($antrolStatus)->sum('jumlah')); @endphp
  <div class="mg-mini">
    <div><small>Booking</small><b>{{ F::n(collect($antrolStatus)->sum('jumlah')) }}</b></div>
    @foreach($antrolStatus as $a)<div><small>{{ $a['status'] === 'Checkin' ? 'Check-in' : $a['status'] }}</small><b>{{ F::n($a['jumlah']) }}</b><span>{{ F::pf($a['jumlah'], $aTot) }}%</span></div>@endforeach
  </div>
  @if(!empty($antrolTimes['pasien']))
    @php $stages = [['Tunggu admisi', 'tunggu_admisi'], ['Layanan admisi', 'layan_admisi'], ['Tunggu poli', 'tunggu_poli'], ['Layanan dokter', 'layan_dokter'], ['Tunggu farmasi', 'tunggu_farmasi'], ['Layanan farmasi', 'layan_farmasi']]; @endphp
    <h3 class="mg-sub">Rata-rata waktu per tahap ({{ F::n($antrolTimes['pasien']) }} pasien, total di rumah sakit sekitar {{ F::n($antrolTimes['total_di_rs']) }} menit)</h3>
    <div class="mg-stages">@foreach($stages as $i => [$lab, $k])<div class="st st{{ $i }}" style="flex:{{ max(0.6, (float) ($antrolTimes[$k] ?? 0)) }}" title="{{ $lab }}"><b>{{ F::n($antrolTimes[$k]) }}′</b><small>{{ $lab }}</small></div>@endforeach</div>
  @endif
  <div class="table-scroll" style="margin-top:14px"><table class="mg-tbl"><thead><tr><th>Poli BPJS</th><th class="r">Booking</th><th class="r">Check-in</th><th class="r">Batal</th><th class="r">Belum datang</th><th class="r">Tingkat hadir</th></tr></thead><tbody>
  @forelse($antrolClinic as $a)<tr><td>{{ $a['poli'] }}</td><td class="r">{{ F::n($a['booking']) }}</td><td class="r">{{ F::n($a['checkin']) }}</td><td class="r">{{ F::n($a['batal']) }}</td><td class="r">{{ F::n($a['belum_datang']) }}</td><td class="r"><b>{{ F::pf($a['checkin'], $a['booking']) }}%</b></td></tr>
  @empty<tr><td colspan="6" class="mg-empty">Belum ada data antrean online pada periode ini.</td></tr>@endforelse
  </tbody></table></div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => '10 diagnosa terbanyak', 'csv' => 'diagnosa'])
    <table class="mg-tbl"><thead><tr><th>Diagnosa</th><th class="r">Ralan</th><th class="r">Ranap</th><th class="r">Total</th></tr></thead><tbody>
    @forelse($diagnoses as $d)<tr><td><small class="code">{{ $d['kode'] }}</small> {{ $d['diagnosa'] }}</td><td class="r">{{ F::n($d['rawat_jalan']) }}</td><td class="r">{{ F::n($d['rawat_inap']) }}</td><td class="r"><b>{{ F::n($d['total']) }}</b></td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Rujukan masuk terbanyak', 'csv' => 'rujukan-masuk'])
    @php $rt = max(1, collect($referrals)->max('pasien')); @endphp
    @forelse($referrals as $r)<div class="mg-row"><span>{{ $r['perujuk'] }}</span><b>{{ F::n($r['pasien']) }} <small>({{ F::n($r['dirawat']) }} dirawat)</small></b><i class="bar" style="width:{{ F::pct($r['pasien'], $rt) }}%"></i></div>
    @empty<p class="mg-empty">Tidak ada data rujukan masuk.</p>@endforelse
  </div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Asal pasien', 'csv' => 'wilayah'])
    @php $rt = max(1, collect($regions)->max('kunjungan')); @endphp
    @forelse($regions as $r)<div class="mg-row"><span>{{ $r['kecamatan'] }}</span><b>{{ F::n($r['kunjungan']) }}</b><i class="bar" style="width:{{ F::pct($r['kunjungan'], $rt) }}%"></i></div>
    @empty<p class="mg-empty">Tidak ada data.</p>@endforelse
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Umur dan jenis kelamin', 'csv' => 'demografi'])
    <table class="mg-tbl"><thead><tr><th>Kelompok umur</th><th class="r">Laki-laki</th><th class="r">Perempuan</th><th class="r">Total</th></tr></thead><tbody>
    @forelse($demography as $d)<tr><td>{{ $d['kelompok_umur'] }}</td><td class="r">{{ F::n($d['laki_laki']) }}</td><td class="r">{{ F::n($d['perempuan']) }}</td><td class="r"><b>{{ F::n($d['total']) }}</b></td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
</div>
