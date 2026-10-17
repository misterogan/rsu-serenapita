@use('App\Support\Fmt', 'F')
@include('admin.management._errors')
<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Ringkasan penunjang'])
  <div class="mg-mini">
    <div><small>Laboratorium</small><b>{{ F::n($support['lab_pasien'] ?? 0) }}</b><span>pasien @if($fin)· {{ F::rps($support['lab_nilai'] ?? 0) }}@endif</span></div>
    <div><small>Radiologi</small><b>{{ F::n($support['rad_pasien'] ?? 0) }}</b><span>pasien @if($fin)· {{ F::rps($support['rad_nilai'] ?? 0) }}@endif</span></div>
    <div><small>Operasi</small><b>{{ F::n($support['operasi'] ?? 0) }}</b><span>tindakan</span></div>
    <div><small>Resep</small><b>{{ F::n($support['resep'] ?? 0) }}</b><span>lembar</span></div>
    <div><small>Rujukan keluar</small><b>{{ F::n($support['rujukan_keluar'] ?? 0) }}</b><span>pasien</span></div>
  </div>
</div>

<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Lama hasil laboratorium dan radiologi', 'csv' => 'hasil-penunjang', 'note' => 'Dari permintaan dokter sampai hasil keluar, untuk permintaan yang sudah ada hasilnya.'])
  <div class="table-scroll"><table class="mg-tbl"><thead><tr><th>Penunjang</th><th>Layanan</th><th class="r">Pemeriksaan</th><th class="r">Rata-rata</th><th style="width:38%">Sebaran waktu</th></tr></thead><tbody>
  @forelse($tat as $t)@php $tt = max(1, $t['pemeriksaan']); @endphp<tr><td><b>{{ $t['penunjang'] }}</b></td><td>{{ $t['layanan'] === 'ranap' ? 'Rawat inap' : 'Rawat jalan' }}</td><td class="r">{{ F::n($t['pemeriksaan']) }}</td><td class="r">{{ F::n($t['rata_menit']) }} menit</td>
    <td><div class="mg-split" title="≤ 1 jam: {{ $t['sd_60_menit'] }}, 1–2 jam: {{ $t['sd_120_menit'] }}, 2–4 jam: {{ $t['sd_240_menit'] }}, > 4 jam: {{ $t['lebih_240_menit'] }}">
      <i class="s0" style="flex:{{ $t['sd_60_menit'] }}"></i><i class="s1" style="flex:{{ $t['sd_120_menit'] }}"></i><i class="s2" style="flex:{{ $t['sd_240_menit'] }}"></i><i class="s3" style="flex:{{ $t['lebih_240_menit'] }}"></i></div></td></tr>
  @empty<tr><td colspan="5" class="mg-empty">Tidak ada data permintaan lab atau radiologi.</td></tr>@endforelse
  </tbody></table></div>
  <div class="mg-legend"><span class="k-s0">≤ 1 jam</span><span class="k-s1">1–2 jam</span><span class="k-s2">2–4 jam</span><span class="k-s3">&gt; 4 jam</span></div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Waktu tunggu resep rawat jalan', 'csv' => 'resep', 'note' => 'Resep ditulis dokter sampai obat diserahkan. Standar pelayanan minimal: non-racikan ≤ 30 menit, racikan ≤ 60 menit.'])
    <table class="mg-tbl"><thead><tr><th>Jenis</th><th class="r">Resep</th><th class="r">Rata-rata</th><th class="r">Sesuai standar</th></tr></thead><tbody>
    @forelse($rx as $x)@php $pc = F::pct($x['sesuai_standar'], $x['resep']); @endphp<tr><td><b>{{ $x['jenis'] }}</b> <small class="code">≤ {{ $x['standar_menit'] }}′</small></td><td class="r">{{ F::n($x['resep']) }}</td><td class="r"><span class="tag {{ $x['rata_menit'] <= $x['standar_menit'] ? 'ok' : 'warn' }}">{{ F::n($x['rata_menit']) }} menit</span></td><td class="r"><b>{{ F::n($pc, 1) }}%</b></td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Tidak ada data jam penyerahan resep.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Laboratorium per kategori', 'csv' => 'lab-kategori'])
    @php $labName = ['PK' => 'Patologi klinik', 'PA' => 'Patologi anatomi', 'MB' => 'Mikrobiologi']; @endphp
    <table class="mg-tbl"><thead><tr><th>Kategori</th><th class="r">Pasien</th><th class="r">Pemeriksaan</th></tr></thead><tbody>
    @forelse($labCat as $c)<tr><td>{{ $labName[$c['kategori']] ?? ($c['kategori'] ?: '—') }}</td><td class="r">{{ F::n($c['pasien']) }}</td><td class="r"><b>{{ F::n($c['pemeriksaan']) }}</b></td></tr>
    @empty<tr><td colspan="3" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Operasi per kategori', 'csv' => 'operasi-kategori'])
    @php $ot = max(1, collect($surg['kategori'])->sum('operasi')); @endphp
    @forelse($surg['kategori'] as $c)<div class="mg-row"><span>{{ $c['kategori'] ?: '—' }}</span><b>{{ F::n($c['operasi']) }}</b><i class="bar" style="width:{{ F::pct($c['operasi'], $ot) }}%"></i></div>
    @empty<p class="mg-empty">Tidak ada operasi.</p>@endforelse
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Operasi per operator', 'csv' => 'operasi-operator'])
    <table class="mg-tbl"><thead><tr><th>Operator</th><th class="r">Operasi</th></tr></thead><tbody>
    @forelse($surg['operator'] as $c)<tr><td>{{ $c['operator'] }}</td><td class="r"><b>{{ F::n($c['operasi']) }}</b></td></tr>
    @empty<tr><td colspan="2" class="mg-empty">Tidak ada operasi.</td></tr>@endforelse
    </tbody></table>
  </div>
</div>

<div class="card mg-card">
  @include('admin.management._head', ['title' => $fin ? '10 obat dengan nilai pemakaian terbesar' : '10 obat utama yang paling banyak dipakai', 'csv' => 'obat'])
  <table class="mg-tbl"><thead><tr><th>Obat</th><th class="r">Jumlah</th>@if($fin)<th class="r">Nilai</th>@endif</tr></thead><tbody>
  @forelse($drugs as $d)<tr><td>{{ $d['obat'] }}</td><td class="r">{{ F::n($d['jumlah']) }}</td>@if($fin)<td class="r"><b>{{ F::rp($d['nilai']) }}</b></td>@endif</tr>
  @empty<tr><td colspan="3" class="mg-empty">Tidak ada data.</td></tr>@endforelse
  </tbody></table>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Obat di bawah stok minimal', 'csv' => 'stok', 'note' => 'Posisi saat ini, jumlah stok dari semua lokasi (gudang, apotek, depo).'])
    <div class="table-scroll mg-scroll"><table class="mg-tbl"><thead><tr><th>Obat</th><th class="r">Stok</th><th class="r">Minimal</th></tr></thead><tbody>
    @forelse($lowStock as $o)<tr><td>{{ $o['obat'] }}<span class="bar hot" style="width:{{ F::pct($o['stok'], $o['stok_minimal']) }}%"></span></td><td class="r"><b>{{ F::n($o['stok']) }}</b></td><td class="r">{{ F::n($o['stok_minimal']) }}</td></tr>
    @empty<tr><td colspan="3" class="mg-empty">Semua obat di atas stok minimal.</td></tr>@endforelse
    </tbody></table></div>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Obat kedaluwarsa dalam 90 hari', 'csv' => 'kedaluwarsa', 'note' => 'Batch yang masih memiliki sisa stok.'])
    <div class="table-scroll mg-scroll"><table class="mg-tbl"><thead><tr><th>Obat</th><th>Batch</th><th class="r">Kedaluwarsa</th><th class="r">Sisa</th></tr></thead><tbody>
    @forelse($expiring as $o)<tr><td>{{ $o['obat'] }}</td><td><small class="code">{{ $o['no_batch'] }}</small></td>
      <td class="r"><span class="tag {{ $o['sisa_hari'] < 0 ? 'bad' : ($o['sisa_hari'] <= 30 ? 'warn' : 'ok') }}">{{ \Illuminate\Support\Carbon::parse($o['kedaluwarsa'])->translatedFormat('d M Y') }}{{ $o['sisa_hari'] < 0 ? ' · lewat' : ' · '.$o['sisa_hari'].' hari' }}</span></td><td class="r">{{ F::n($o['sisa']) }}</td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Tidak ada obat yang mendekati kedaluwarsa.</td></tr>@endforelse
    </tbody></table></div>
  </div>
</div>
