@extends('layouts.admin', ['current' => 'management'])
@section('title', 'Dasbor manajemen')
@php
  $n = fn ($v, $d = 0) => $v === null || $v === '' ? '—' : number_format((float) $v, $d, ',', '.');
  $rp = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');
  $pct = fn ($a, $b) => $b ? round($a / $b * 100, 1) : 0;
  $pf = fn ($a, $b) => number_format($b ? $a / $b * 100 : 0, 1, ',', '.');
  $q = fn (array $extra = []) => array_merge(['periode' => $preset] + ($preset === 'custom' ? ['dari' => $from, 'sampai' => $to] : []), $extra);
  $csv = fn ($key) => route('admin.management.export', [$key] + $q());
@endphp
@section('content')
<x-admin-head module="management" :desc="($ops && $fin ? 'Ringkasan pelayanan, antrean, rawat inap, dan keuangan' : ($fin ? 'Laporan pembayaran, piutang, dan tagihan' : 'Ringkasan pelayanan, antrean, dan rawat inap')).' langsung dari database SIMRS Khanza. Data hanya dibaca, tidak diubah.'">
  @if($configured)<a class="btn" href="{{ route('admin.management', $q(['segarkan' => 1])) }}">Muat ulang data</a>@endif
</x-admin-head>

<form class="card mg-period" method="get" data-noload>
  <div class="mg-chips" role="group" aria-label="Periode">
    @foreach($presets as $k => $label)<a class="mg-chip {{ $preset === $k ? 'on' : '' }}" href="{{ route('admin.management', ['periode' => $k]) }}">{{ $label }}</a>@endforeach
  </div>
  <div class="mg-custom">
    <input type="hidden" name="periode" value="custom">
    <label>Dari <input type="date" name="dari" value="{{ $from }}"></label>
    <label>Sampai <input type="date" name="sampai" value="{{ $to }}"></label>
    <button class="btn sm">Terapkan</button>
  </div>
  <div class="mg-meta">Periode {{ \Illuminate\Support\Carbon::parse($from)->translatedFormat('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->translatedFormat('d M Y') }}@isset($loadedAt) · data disimpan sementara {{ config('serenapita.khanza.cache_minutes') }} menit @endisset</div>
</form>

@if(!$configured)
  <div class="card" style="padding:22px 24px">
    <h2 style="margin-top:0">Hubungkan ke database Khanza</h2>
    <p>Dasbor ini membaca langsung database SIMRS Khanza. Minta tim IT menambahkan pengaturan berikut ke file <code>.env</code> server, lalu jalankan <code>php artisan config:clear</code>.</p>
    <pre class="mg-pre">KHANZA_DB_HOST=192.168.1.10
KHANZA_DB_PORT=3306
KHANZA_DB_DATABASE=sik
KHANZA_DB_USERNAME=dashboard
KHANZA_DB_PASSWORD=password-khusus-baca
KHANZA_POLI_IGD=IGDK</pre>
    <p>Gunakan user database yang <b>hanya boleh membaca</b>. Langkah lengkapnya ada di file <code>database/khanza/README.txt</code> pada proyek website.</p>
  </div>
@elseif($connError)
  <div class="flash err">Tidak bisa terhubung ke database Khanza: {{ $connError }}</div>
@else
  @php
    $s = $summary ?? []; $p = $prev ?? []; $now = $now ?? [];
    $delta = fn ($cur, $old) => $old ? round(($cur - $old) / $old * 100, 1) : null;
    $payTotal = collect($payments ?? [])->sum('total'); $recTotal = collect($receivables ?? [])->sum('total_piutang');
    $bedPct = $pct($now['terisi'] ?? 0, $now['tempat_tidur'] ?? 0);
  @endphp

  @if($errors_)
  <details class="flash err mg-errs"><summary>{{ count($errors_) }} laporan tidak bisa dibaca dari database Khanza. Klik untuk detail (untuk tim IT).</summary>
    <ul>@foreach($errors_ as $k => $m)<li><b>{{ $k }}</b>: {{ $m }}</li>@endforeach</ul></details>
  @endif

  @if($ops)
  <div class="mg-kpis">
    @foreach([
      ['Kunjungan', $s['kunjungan'] ?? 0, $delta($s['kunjungan'] ?? 0, $p['kunjungan'] ?? 0), $n($s['pasien_unik'] ?? 0).' pasien berbeda'],
      ['Rawat jalan', $s['rawat_jalan'] ?? 0, $delta($s['rawat_jalan'] ?? 0, $p['rawat_jalan'] ?? 0), $n($s['pasien_baru'] ?? 0).' pasien baru'],
      ['IGD', $s['igd'] ?? 0, $delta($s['igd'] ?? 0, $p['igd'] ?? 0), $n($s['lanjut_ranap'] ?? 0).' lanjut rawat inap'],
      ['Pasien BPJS', $s['bpjs'] ?? 0, null, $pf($s['bpjs'] ?? 0, $s['kunjungan'] ?? 0).'% dari kunjungan'],
      ['Dirawat saat ini', $now['dirawat'] ?? 0, null, 'TT terisi '.$n($now['terisi'] ?? 0).' dari '.$n($now['tempat_tidur'] ?? 0).' ('.$pf($now['terisi'] ?? 0, $now['tempat_tidur'] ?? 0).'%)'],
      ['Batal daftar', $s['batal'] ?? 0, null, $pf($s['batal'] ?? 0, ($s['kunjungan'] ?? 0) + ($s['batal'] ?? 0)).'% dari pendaftaran'],
    ] as [$label, $val, $dl, $sub])
    <div class="mg-kpi"><small>{{ $label }}</small><b>{{ $n($val) }}</b>
      <span>{{ $sub }}</span>
      @if($dl !== null)<em class="{{ $dl >= 0 ? 'up' : 'down' }}">{{ $dl >= 0 ? '▲' : '▼' }} {{ $n(abs($dl), 1) }}% vs periode sebelumnya</em>@endif
    </div>
    @endforeach
  </div>

  {{-- Tren kunjungan --}}
  <div class="card mg-card">
    <div class="mg-h"><h2>Tren kunjungan harian</h2><a href="{{ $csv('tren') }}">Unduh CSV</a></div>
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
      <div class="mg-h"><h2>Kunjungan per poliklinik</h2><a href="{{ $csv('poli') }}">Unduh CSV</a></div>
      @php $mx = max(1, collect($clinics)->max('kunjungan')); @endphp
      <table class="mg-tbl"><thead><tr><th>Poliklinik</th><th class="r">Kunjungan</th><th class="r">Baru</th><th class="r">BPJS</th><th class="r">Batal</th></tr></thead><tbody>
      @forelse($clinics as $c)<tr><td>{{ $c['poliklinik'] }}<span class="bar" style="width:{{ $pct($c['kunjungan'], $mx) }}%"></span></td><td class="r"><b>{{ $n($c['kunjungan']) }}</b></td><td class="r">{{ $n($c['baru']) }}</td><td class="r">{{ $n($c['bpjs']) }}</td><td class="r">{{ $n($c['batal']) }}</td></tr>
      @empty<tr><td colspan="5" class="mg-empty">Tidak ada data.</td></tr>@endforelse
      </tbody></table>
    </div>
    <div class="card mg-card">
      <div class="mg-h"><h2>Cara bayar</h2><a href="{{ $csv('penjab') }}">Unduh CSV</a></div>
      @php $tot = max(1, collect($payers)->sum('kunjungan')); @endphp
      @forelse($payers as $c)<div class="mg-row"><span>{{ $c['cara_bayar'] }}</span><b>{{ $n($c['kunjungan']) }} <small>({{ $pf($c['kunjungan'], $tot) }}%)</small></b><i class="bar" style="width:{{ $pct($c['kunjungan'], $tot) }}%"></i></div>
      @empty<p class="mg-empty">Tidak ada data.</p>@endforelse
    </div>
  </div>

  {{-- Dokter --}}
  <div class="card mg-card">
    <div class="mg-h"><h2>Kinerja dokter rawat jalan</h2><a href="{{ $csv('dokter') }}">Unduh CSV</a></div>
    <p class="mg-note"><b>Waktu tunggu</b>: dari jam daftar sampai pemeriksaan pertama dicatat dokter. <b>Lama layanan</b>: dari dipanggil dokter sampai selesai di poli, hanya pasien yang tercatat di antrean online (task 4 sampai 5).</p>
    <div class="table-scroll"><table class="mg-tbl"><thead><tr><th>Dokter</th><th>Poliklinik</th><th class="r">Pasien</th><th class="r">Rata-rata tunggu</th><th class="r">Rata-rata layanan</th><th class="r">Tercatat antrol</th></tr></thead><tbody>
    @forelse($doctors as $d)<tr><td><b>{{ $d['dokter'] }}</b></td><td>{{ $d['poliklinik'] }}</td><td class="r">{{ $n($d['pasien']) }}</td>
      <td class="r"><span class="tag {{ ($d['rata_tunggu_menit'] ?? 0) > 60 ? 'warn' : 'ok' }}">{{ $n($d['rata_tunggu_menit']) }} menit</span></td>
      <td class="r">{{ $d['rata_layanan_menit'] !== null ? $n($d['rata_layanan_menit']).' menit' : '—' }}</td><td class="r">{{ $n($d['tercatat_antrol']) }}</td></tr>
    @empty<tr><td colspan="6" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table></div>
  </div>

  {{-- Antrol --}}
  <div class="card mg-card">
    <div class="mg-h"><h2>Antrean online (Mobile JKN)</h2><a href="{{ $csv('antrol') }}">Unduh CSV</a></div>
    @php $aTot = max(1, collect($antrolStatus)->sum('jumlah')); $ck = collect($antrolStatus)->firstWhere('status', 'Checkin')['jumlah'] ?? 0; @endphp
    <div class="mg-mini">
      <div><small>Booking</small><b>{{ $n(collect($antrolStatus)->sum('jumlah')) }}</b></div>
      @foreach($antrolStatus as $a)<div><small>{{ $a['status'] === 'Checkin' ? 'Check-in' : $a['status'] }}</small><b>{{ $n($a['jumlah']) }}</b><span>{{ $pf($a['jumlah'], $aTot) }}%</span></div>@endforeach
    </div>
    @if(!empty($antrolTimes['pasien']))
      @php $stages = [['Tunggu admisi', 'tunggu_admisi'], ['Layanan admisi', 'layan_admisi'], ['Tunggu poli', 'tunggu_poli'], ['Layanan dokter', 'layan_dokter'], ['Tunggu farmasi', 'tunggu_farmasi'], ['Layanan farmasi', 'layan_farmasi']]; $sumMin = max(1, collect($stages)->sum(fn ($x) => (float) ($antrolTimes[$x[1]] ?? 0))); @endphp
      <h3 class="mg-sub">Rata-rata waktu per tahap ({{ $n($antrolTimes['pasien']) }} pasien, total di rumah sakit sekitar {{ $n($antrolTimes['total_di_rs']) }} menit)</h3>
      <div class="mg-stages">@foreach($stages as $i => [$lab, $k])<div class="st st{{ $i }}" style="flex:{{ max(0.6, (float) ($antrolTimes[$k] ?? 0)) }}" title="{{ $lab }}"><b>{{ $n($antrolTimes[$k]) }}′</b><small>{{ $lab }}</small></div>@endforeach</div>
    @endif
    <div class="table-scroll" style="margin-top:14px"><table class="mg-tbl"><thead><tr><th>Poli BPJS</th><th class="r">Booking</th><th class="r">Check-in</th><th class="r">Batal</th><th class="r">Belum datang</th><th class="r">Tingkat hadir</th></tr></thead><tbody>
    @forelse($antrolClinic as $a)<tr><td>{{ $a['poli'] }}</td><td class="r">{{ $n($a['booking']) }}</td><td class="r">{{ $n($a['checkin']) }}</td><td class="r">{{ $n($a['batal']) }}</td><td class="r">{{ $n($a['belum_datang']) }}</td><td class="r"><b>{{ $pf($a['checkin'], $a['booking']) }}%</b></td></tr>
    @empty<tr><td colspan="6" class="mg-empty">Belum ada data antrean online pada periode ini.</td></tr>@endforelse
    </tbody></table></div>
  </div>

  {{-- Rawat inap --}}
  <div class="card mg-card">
    <div class="mg-h"><h2>Rawat inap</h2><a href="{{ $csv('bangsal') }}">Unduh CSV bangsal</a></div>
    @php $ideal = ['bor' => [60, 85, '%', 'BOR', 'Pemakaian tempat tidur'], 'alos' => [6, 9, ' hari', 'ALOS', 'Rata-rata lama dirawat'], 'toi' => [1, 3, ' hari', 'TOI', 'Rata-rata TT kosong'], 'bto' => [null, null, ' kali', 'BTO', 'Pemakaian TT per periode'], 'gdr' => [null, 45, '‰', 'GDR', 'Angka kematian kasar'], 'ndr' => [null, 25, '‰', 'NDR', 'Kematian ≥ 48 jam']]; @endphp
    <div class="mg-ind">
      @foreach($ideal as $k => [$lo, $hi, $unit, $code, $desc])
        @php $v = $ind[$k] ?? null; $ok = $v === null ? null : (($lo === null || $v >= $lo) && ($hi === null || $v <= $hi)); @endphp
        <div class="ind {{ $ok === null ? '' : ($ok ? 'ok' : 'warn') }}"><small>{{ $code }}</small><b>{{ $v === null ? '—' : $n($v, 1).$unit }}</b><span>{{ $desc }}</span>@if($lo !== null || $hi !== null)<em>Ideal {{ $lo !== null ? $lo.'–' : '≤ ' }}{{ $hi }}{{ trim($unit) === '%' ? '%' : $unit }}</em>@endif</div>
      @endforeach
    </div>
    <p class="mg-note">Periode {{ $days }} hari: {{ $n($ind['masuk'] ?? 0) }} pasien masuk, {{ $n($ind['keluar'] ?? 0) }} pasien keluar, {{ $n($ind['meninggal'] ?? 0) }} meninggal, {{ $n($ind['hari_perawatan'] ?? 0) }} hari perawatan, {{ $n($ind['tempat_tidur'] ?? 0) }} tempat tidur aktif. Nilai ideal mengacu standar Kementerian Kesehatan. BTO dihitung untuk periode yang dipilih.</p>
    <div class="mg-grid">
      <div><h3 class="mg-sub">Okupansi saat ini</h3>
        <table class="mg-tbl"><thead><tr><th>Bangsal</th><th>Kelas</th><th class="r">TT</th><th class="r">Terisi</th><th class="r">Kosong</th></tr></thead><tbody>
        @forelse($wards as $w)<tr><td>{{ $w['bangsal'] }}<span class="bar {{ $pf($w['terisi'], $w['tempat_tidur']) >= 90 ? 'hot' : '' }}" style="width:{{ $pct($w['terisi'], $w['tempat_tidur']) }}%"></span></td><td>{{ $w['kelas'] }}</td><td class="r">{{ $n($w['tempat_tidur']) }}</td><td class="r"><b>{{ $n($w['terisi']) }}</b></td><td class="r">{{ $n($w['kosong']) }}</td></tr>
        @empty<tr><td colspan="5" class="mg-empty">Tidak ada data.</td></tr>@endforelse
        </tbody></table></div>
      <div><h3 class="mg-sub">Status pulang <a class="mg-a" href="{{ $csv('status-pulang') }}">CSV</a></h3>
        @php $dt = max(1, collect($discharge)->sum('pasien')); @endphp
        @forelse($discharge as $d)<div class="mg-row"><span>{{ $d['status_pulang'] }}</span><b>{{ $n($d['pasien']) }} <small>({{ $pf($d['pasien'], $dt) }}%)</small></b><i class="bar {{ $d['status_pulang'] === 'Meninggal' ? 'hot' : '' }}" style="width:{{ $pct($d['pasien'], $dt) }}%"></i></div>
        @empty<p class="mg-empty">Tidak ada pasien keluar pada periode ini.</p>@endforelse</div>
    </div>
  </div>

  @endif

  @if($fin)
  {{-- Keuangan --}}
  <div class="card mg-card">
    <div class="mg-h"><h2>Keuangan</h2></div>
    <div class="mg-mini">
      <div><small>Pembayaran diterima</small><b>{{ $rp($payTotal) }}</b><span>tunai dan non-tunai</span></div>
      <div><small>Piutang baru</small><b>{{ $rp($recTotal) }}</b><span>BPJS dan asuransi</span></div>
      <div><small>Total pendapatan</small><b>{{ $rp($payTotal + $recTotal) }}</b><span>pembayaran + piutang</span></div>
    </div>
    <div class="mg-grid">
      <div><h3 class="mg-sub">Pembayaran per cara bayar <a class="mg-a" href="{{ $csv('pembayaran') }}">CSV</a></h3>
        <table class="mg-tbl"><thead><tr><th>Layanan</th><th>Cara bayar</th><th class="r">Nota</th><th class="r">Total</th></tr></thead><tbody>
        @forelse($payments as $x)<tr><td>{{ $x['jenis'] }}</td><td>{{ $x['cara_bayar'] }}</td><td class="r">{{ $n($x['nota']) }}</td><td class="r"><b>{{ $rp($x['total']) }}</b></td></tr>
        @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
        </tbody></table>
        <h3 class="mg-sub" style="margin-top:18px">Piutang per penjamin <a class="mg-a" href="{{ $csv('piutang') }}">CSV</a></h3>
        <table class="mg-tbl"><thead><tr><th>Penjamin</th><th class="r">Pasien</th><th class="r">Piutang</th><th class="r">Sisa</th></tr></thead><tbody>
        @forelse($receivables as $x)<tr><td>{{ $x['penjamin'] }}</td><td class="r">{{ $n($x['pasien']) }}</td><td class="r"><b>{{ $rp($x['total_piutang']) }}</b></td><td class="r">{{ $rp($x['sisa_piutang']) }}</td></tr>
        @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
        </tbody></table></div>
      <div><h3 class="mg-sub">Komposisi tagihan per kategori <a class="mg-a" href="{{ $csv('billing') }}">CSV</a></h3>
        @php $bt = max(1, collect($billing)->sum('total')); @endphp
        @forelse($billing as $b)<div class="mg-row"><span>{{ $b['kategori'] }}</span><b>{{ $rp($b['total']) }} <small>({{ $pf($b['total'], $bt) }}%)</small></b><i class="bar" style="width:{{ max(0, $pct($b['total'], $bt)) }}%"></i></div>
        @empty<p class="mg-empty">Tidak ada data billing pada periode ini.</p>@endforelse
        <p class="mg-note">Dihitung dari tabel billing per tanggal bayar, tanpa baris judul dan subtotal.</p></div>
    </div>
  </div>

  @endif

  {{-- Penunjang --}}
  <div class="card mg-card">
    <div class="mg-h"><h2>Pelayanan penunjang</h2><a href="{{ $csv('obat') }}">Unduh CSV obat</a></div>
    <div class="mg-mini">
      <div><small>Laboratorium</small><b>{{ $n($support['lab_pasien'] ?? 0) }}</b><span>pasien @if($fin)· {{ $rp($support['lab_nilai'] ?? 0) }}@endif</span></div>
      <div><small>Radiologi</small><b>{{ $n($support['rad_pasien'] ?? 0) }}</b><span>pasien @if($fin)· {{ $rp($support['rad_nilai'] ?? 0) }}@endif</span></div>
      <div><small>Operasi</small><b>{{ $n($support['operasi'] ?? 0) }}</b><span>tindakan</span></div>
      <div><small>Resep</small><b>{{ $n($support['resep'] ?? 0) }}</b><span>lembar</span></div>
      <div><small>Rujukan keluar</small><b>{{ $n($support['rujukan_keluar'] ?? 0) }}</b><span>pasien</span></div>
    </div>
    <h3 class="mg-sub">{{ $fin ? '10 obat dengan nilai pemakaian terbesar' : '10 obat utama yang paling banyak dipakai' }}</h3>
    <table class="mg-tbl"><thead><tr><th>Obat</th><th class="r">Jumlah</th>@if($fin)<th class="r">Nilai</th>@endif</tr></thead><tbody>
    @forelse($drugs as $d)<tr><td>{{ $d['obat'] }}</td><td class="r">{{ $n($d['jumlah']) }}</td>@if($fin)<td class="r"><b>{{ $rp($d['nilai']) }}</b></td>@endif</tr>
    @empty<tr><td colspan="3" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>

  @if($ops)
  <div class="mg-grid">
    <div class="card mg-card">
      <div class="mg-h"><h2>10 diagnosa terbanyak</h2><a href="{{ $csv('diagnosa') }}">Unduh CSV</a></div>
      <table class="mg-tbl"><thead><tr><th>Diagnosa</th><th class="r">Ralan</th><th class="r">Ranap</th><th class="r">Total</th></tr></thead><tbody>
      @forelse($diagnoses as $d)<tr><td><small class="code">{{ $d['kode'] }}</small> {{ $d['diagnosa'] }}</td><td class="r">{{ $n($d['rawat_jalan']) }}</td><td class="r">{{ $n($d['rawat_inap']) }}</td><td class="r"><b>{{ $n($d['total']) }}</b></td></tr>
      @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
      </tbody></table>
    </div>
    <div class="card mg-card">
      <div class="mg-h"><h2>Asal pasien</h2><a href="{{ $csv('wilayah') }}">Unduh CSV</a></div>
      @php $rt = max(1, collect($regions)->max('kunjungan')); @endphp
      @forelse($regions as $r)<div class="mg-row"><span>{{ $r['kecamatan'] }}</span><b>{{ $n($r['kunjungan']) }}</b><i class="bar" style="width:{{ $pct($r['kunjungan'], $rt) }}%"></i></div>
      @empty<p class="mg-empty">Tidak ada data.</p>@endforelse
      <div class="mg-h" style="margin-top:20px"><h2>Umur dan jenis kelamin</h2><a href="{{ $csv('demografi') }}">Unduh CSV</a></div>
      <table class="mg-tbl"><thead><tr><th>Kelompok umur</th><th class="r">Laki-laki</th><th class="r">Perempuan</th><th class="r">Total</th></tr></thead><tbody>
      @forelse($demography as $d)<tr><td>{{ $d['kelompok_umur'] }}</td><td class="r">{{ $n($d['laki_laki']) }}</td><td class="r">{{ $n($d['perempuan']) }}</td><td class="r"><b>{{ $n($d['total']) }}</b></td></tr>
      @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
      </tbody></table>
    </div>
  </div>
  @endif
@endif
@endsection
