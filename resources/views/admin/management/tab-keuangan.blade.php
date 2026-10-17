@use('App\Support\Fmt', 'F')
@include('admin.management._errors')
@php
  $pay = collect($payments)->sum('total'); $rec = collect($receivables)->sum('total_piutang'); $paidIn = collect($paid)->sum('diterima');
  $sisa = collect($aging)->sum('total_sisa'); $hut = collect($payables)->sum('sisa'); $exp = collect($expenses)->sum('total');
@endphp
<div class="mg-kpis">
  <div class="mg-kpi"><small>Pembayaran diterima</small><b class="sm">{{ F::rps($pay) }}</b><span>kasir rawat jalan dan inap</span></div>
  <div class="mg-kpi"><small>Piutang baru</small><b class="sm">{{ F::rps($rec) }}</b><span>BPJS dan asuransi</span></div>
  <div class="mg-kpi"><small>Pelunasan piutang</small><b class="sm">{{ F::rps($paidIn) }}</b><span>diterima pada periode</span></div>
  <div class="mg-kpi"><small>Sisa piutang</small><b class="sm">{{ F::rps($sisa) }}</b><span>posisi hari ini</span></div>
  <div class="mg-kpi"><small>Hutang supplier</small><b class="sm">{{ F::rps($hut) }}</b><span>posisi hari ini</span></div>
  <div class="mg-kpi"><small>Pengeluaran harian</small><b class="sm">{{ F::rps($exp) }}</b><span>pada periode</span></div>
</div>

<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Umur piutang (posisi hari ini)', 'csv' => 'umur-piutang', 'note' => 'Sisa piutang yang belum lunas, dikelompokkan dari tanggal piutang.'])
  <div class="table-scroll"><table class="mg-tbl"><thead><tr><th>Penjamin</th><th class="r">Pasien</th><th class="r">0–30 hari</th><th class="r">31–60 hari</th><th class="r">61–90 hari</th><th class="r">&gt; 90 hari</th><th class="r">Total</th></tr></thead><tbody>
  @forelse($aging as $a)<tr><td><b>{{ $a['penjamin'] }}</b>
      <div class="mg-split sm"><i class="s0" style="flex:{{ (float) $a['hari_0_30'] }}"></i><i class="s1" style="flex:{{ (float) $a['hari_31_60'] }}"></i><i class="s2" style="flex:{{ (float) $a['hari_61_90'] }}"></i><i class="s3" style="flex:{{ (float) $a['lebih_90'] }}"></i></div></td>
    <td class="r">{{ F::n($a['pasien']) }}</td><td class="r">{{ F::rps($a['hari_0_30']) }}</td><td class="r">{{ F::rps($a['hari_31_60']) }}</td><td class="r">{{ F::rps($a['hari_61_90']) }}</td>
    <td class="r">@if($a['lebih_90'] > 0)<span class="tag bad">{{ F::rps($a['lebih_90']) }}</span>@else{{ F::rps(0) }}@endif</td><td class="r"><b>{{ F::rps($a['total_sisa']) }}</b></td></tr>
  @empty<tr><td colspan="7" class="mg-empty">Tidak ada piutang yang belum lunas.</td></tr>@endforelse
  </tbody></table></div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Pembayaran per cara bayar', 'csv' => 'pembayaran'])
    <table class="mg-tbl"><thead><tr><th>Layanan</th><th>Cara bayar</th><th class="r">Nota</th><th class="r">Total</th></tr></thead><tbody>
    @forelse($payments as $x)<tr><td>{{ $x['jenis'] }}</td><td>{{ $x['cara_bayar'] }}</td><td class="r">{{ F::n($x['nota']) }}</td><td class="r"><b>{{ F::rp($x['total']) }}</b></td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Piutang baru dan pelunasan', 'csv' => 'pelunasan'])
    <table class="mg-tbl"><thead><tr><th>Penjamin</th><th class="r">Piutang baru</th><th class="r">Pelunasan</th><th class="r">Tidak terbayar</th></tr></thead><tbody>
    @php $names = collect($receivables)->pluck('penjamin')->merge(collect($paid)->pluck('penjamin'))->unique(); @endphp
    @forelse($names as $nm)@php $r1 = collect($receivables)->firstWhere('penjamin', $nm); $r2 = collect($paid)->firstWhere('penjamin', $nm); @endphp
      <tr><td>{{ $nm }}</td><td class="r"><b>{{ F::rps($r1['total_piutang'] ?? 0) }}</b></td><td class="r">{{ F::rps($r2['diterima'] ?? 0) }}</td><td class="r">{{ F::rps($r2['tidak_terbayar'] ?? 0) }}</td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Pendapatan per poliklinik', 'csv' => 'pendapatan-poli', 'note' => 'Dari tagihan (billing) per tanggal bayar.'])
    @php $mx = max(1, collect($byClinic)->max('pendapatan')); @endphp
    <table class="mg-tbl"><thead><tr><th>Poliklinik</th><th class="r">Pasien</th><th class="r">Pendapatan</th></tr></thead><tbody>
    @forelse($byClinic as $x)<tr><td>{{ $x['poliklinik'] }}<span class="bar" style="width:{{ F::pct($x['pendapatan'], $mx) }}%"></span></td><td class="r">{{ F::n($x['pasien']) }}</td><td class="r"><b>{{ F::rps($x['pendapatan']) }}</b></td></tr>
    @empty<tr><td colspan="3" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Komposisi tagihan per kategori', 'csv' => 'billing'])
    @php $bt = max(1, collect($billing)->sum('total')); @endphp
    @forelse($billing as $b)<div class="mg-row"><span>{{ $b['kategori'] }}</span><b>{{ F::rps($b['total']) }} <small>({{ F::pf($b['total'], $bt) }}%)</small></b><i class="bar" style="width:{{ max(0, F::pct($b['total'], $bt)) }}%"></i></div>
    @empty<p class="mg-empty">Tidak ada data billing.</p>@endforelse
  </div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Pendapatan per dokter', 'csv' => 'pendapatan-dokter', 'note' => 'Berdasarkan dokter saat pendaftaran. 20 teratas.'])
    <div class="table-scroll mg-scroll"><table class="mg-tbl"><thead><tr><th>Dokter</th><th class="r">Pasien</th><th class="r">Pendapatan</th></tr></thead><tbody>
    @forelse($byDoctor as $x)<tr><td>{{ $x['dokter'] }}</td><td class="r">{{ F::n($x['pasien']) }}</td><td class="r"><b>{{ F::rps($x['pendapatan']) }}</b></td></tr>
    @empty<tr><td colspan="3" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table></div>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Jasa tindakan dokter', 'csv' => 'jasa-medis', 'note' => 'Komponen jasa dokter dari tindakan rawat jalan, rawat inap, dan operator operasi. Untuk bahan rekap jasa pelayanan.'])
    <div class="table-scroll mg-scroll"><table class="mg-tbl"><thead><tr><th>Dokter</th><th class="r">Tindakan</th><th class="r">Jasa</th></tr></thead><tbody>
    @forelse($fees as $x)<tr><td>{{ $x['dokter'] }}</td><td class="r">{{ F::n($x['tindakan']) }}</td><td class="r"><b>{{ F::rps($x['jasa_tindakan']) }}</b></td></tr>
    @empty<tr><td colspan="3" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table></div>
  </div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Hutang supplier (posisi hari ini)', 'csv' => 'hutang'])
    <table class="mg-tbl"><thead><tr><th>Supplier</th><th class="r">Faktur</th><th class="r">Sisa</th><th class="r">Lewat tempo</th></tr></thead><tbody>
    @forelse($payables as $x)<tr><td>{{ $x['supplier'] }}</td><td class="r">{{ F::n($x['faktur']) }}</td><td class="r"><b>{{ F::rps($x['sisa']) }}</b></td><td class="r">@if($x['lewat_jatuh_tempo'] > 0)<span class="tag bad">{{ F::rps($x['lewat_jatuh_tempo']) }}</span>@else — @endif</td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Tidak ada hutang yang belum lunas.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Pengeluaran harian per kategori', 'csv' => 'pengeluaran'])
    @php $et = max(1, $exp); @endphp
    @forelse($expenses as $x)<div class="mg-row"><span>{{ $x['kategori'] }} <small>({{ F::n($x['transaksi']) }} transaksi)</small></span><b>{{ F::rps($x['total']) }}</b><i class="bar" style="width:{{ F::pct($x['total'], $et) }}%"></i></div>
    @empty<p class="mg-empty">Tidak ada pengeluaran tercatat.</p>@endforelse
  </div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Margin farmasi', 'csv' => 'margin-farmasi', 'note' => 'Penjualan obat ke pasien dibandingkan harga beli.'])
    <table class="mg-tbl"><thead><tr><th>Layanan</th><th class="r">Penjualan</th><th class="r">Modal</th><th class="r">Margin</th></tr></thead><tbody>
    @forelse($margin as $x)<tr><td>{{ $x['layanan'] === 'Ranap' ? 'Rawat inap' : 'Rawat jalan' }}</td><td class="r">{{ F::rps($x['penjualan']) }}</td><td class="r">{{ F::rps($x['modal']) }}</td><td class="r"><b>{{ F::rps($x['margin']) }}</b> <small class="code">{{ F::pf($x['margin'], $x['penjualan']) }}%</small></td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Nilai penunjang dan obat', 'csv' => 'obat'])
    <div class="mg-mini">
      <div><small>Laboratorium</small><b>{{ F::rps($support['lab_nilai'] ?? 0) }}</b><span>{{ F::n($support['lab_pasien'] ?? 0) }} pasien</span></div>
      <div><small>Radiologi</small><b>{{ F::rps($support['rad_nilai'] ?? 0) }}</b><span>{{ F::n($support['rad_pasien'] ?? 0) }} pasien</span></div>
    </div>
    <table class="mg-tbl" style="margin-top:10px"><thead><tr><th>Obat (10 nilai terbesar)</th><th class="r">Nilai</th></tr></thead><tbody>
    @forelse($drugs as $d)<tr><td>{{ $d['obat'] }}</td><td class="r"><b>{{ F::rps($d['nilai']) }}</b></td></tr>
    @empty<tr><td colspan="2" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
</div>
