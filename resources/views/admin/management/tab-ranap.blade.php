@use('App\Support\Fmt', 'F')
@include('admin.management._errors')
@php $readmPct = F::pct($readm['dirawat_ulang'] ?? 0, $readm['pasien_pulang'] ?? 0); @endphp
<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Indikator rawat inap'])
  @php $ideal = ['bor' => [60, 85, '%', 'BOR', 'Pemakaian tempat tidur'], 'alos' => [6, 9, ' hari', 'ALOS', 'Rata-rata lama dirawat'], 'toi' => [1, 3, ' hari', 'TOI', 'Rata-rata TT kosong'], 'bto' => [null, null, ' kali', 'BTO', 'Pemakaian TT per periode'], 'gdr' => [null, 45, '‰', 'GDR', 'Angka kematian kasar'], 'ndr' => [null, 25, '‰', 'NDR', 'Kematian ≥ 48 jam']]; @endphp
  <div class="mg-ind">
    @foreach($ideal as $k => [$lo, $hi, $unit, $code, $desc])
      @php $v = $ind[$k] ?? null; $ok = $v === null ? null : (($lo === null || $v >= $lo) && ($hi === null || $v <= $hi)); @endphp
      <div class="ind {{ $ok === null ? '' : ($ok ? 'ok' : 'warn') }}"><small>{{ $code }}</small><b>{{ $v === null ? '—' : F::n($v, 1).$unit }}</b><span>{{ $desc }}</span>@if($lo !== null || $hi !== null)<em>Ideal {{ $lo !== null ? $lo.'–' : '≤ ' }}{{ $hi }}{{ $unit }}</em>@endif</div>
    @endforeach
    <div class="ind {{ ($readm['pasien_pulang'] ?? 0) ? ($readmPct <= 5 ? 'ok' : 'warn') : '' }}"><small>READMISI</small><b>{{ ($readm['pasien_pulang'] ?? 0) ? F::n($readmPct, 1).'%' : '—' }}</b><span>{{ F::n($readm['dirawat_ulang'] ?? 0) }} dari {{ F::n($readm['pasien_pulang'] ?? 0) }} pasien kembali ≤ 30 hari</span><em>Batas ≤ 5%</em></div>
  </div>
  <p class="mg-note">Periode {{ $days }} hari: {{ F::n($ind['masuk'] ?? 0) }} pasien masuk, {{ F::n($ind['keluar'] ?? 0) }} pasien keluar, {{ F::n($ind['meninggal'] ?? 0) }} meninggal, {{ F::n($ind['hari_perawatan'] ?? 0) }} hari perawatan, {{ F::n($ind['tempat_tidur'] ?? 0) }} tempat tidur aktif. Pasien yang dirawat saat ini: {{ F::n($now['dirawat'] ?? 0) }}.</p>
</div>

<div class="mg-grid">
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Okupansi saat ini', 'csv' => 'bangsal'])
    <table class="mg-tbl"><thead><tr><th>Bangsal</th><th>Kelas</th><th class="r">TT</th><th class="r">Terisi</th><th class="r">Kosong</th></tr></thead><tbody>
    @forelse($wards as $w)<tr><td>{{ $w['bangsal'] }}<span class="bar {{ F::pct($w['terisi'], $w['tempat_tidur']) >= 90 ? 'hot' : '' }}" style="width:{{ F::pct($w['terisi'], $w['tempat_tidur']) }}%"></span></td><td>{{ $w['kelas'] }}</td><td class="r">{{ F::n($w['tempat_tidur']) }}</td><td class="r"><b>{{ F::n($w['terisi']) }}</b></td><td class="r">{{ F::n($w['kosong']) }}</td></tr>
    @empty<tr><td colspan="5" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table>
  </div>
  <div class="card mg-card">
    @include('admin.management._head', ['title' => 'Status pulang', 'csv' => 'status-pulang'])
    @php $dt = max(1, collect($discharge)->sum('pasien')); @endphp
    @forelse($discharge as $d)<div class="mg-row"><span>{{ $d['status_pulang'] }}</span><b>{{ F::n($d['pasien']) }} <small>({{ F::pf($d['pasien'], $dt) }}%)</small></b><i class="bar {{ $d['status_pulang'] === 'Meninggal' ? 'hot' : '' }}" style="width:{{ F::pct($d['pasien'], $dt) }}%"></i></div>
    @empty<p class="mg-empty">Tidak ada pasien keluar pada periode ini.</p>@endforelse
  </div>
</div>

<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Rawat inap per dokter penanggung jawab (DPJP)', 'csv' => 'dpjp', 'note' => 'Pasien yang pulang pada periode ini. Satu pasien bisa memiliki lebih dari satu DPJP.'])
  <div class="table-scroll"><table class="mg-tbl"><thead><tr><th>Dokter</th><th class="r">Pasien pulang</th><th class="r">Rata-rata lama</th><th class="r">Meninggal</th><th class="r">Pulang paksa / APS</th></tr></thead><tbody>
  @forelse($dpjp as $d)<tr><td><b>{{ $d['dokter'] }}</b></td><td class="r">{{ F::n($d['pasien_pulang']) }}</td><td class="r">{{ F::n($d['rata_lama_hari'], 1) }} hari</td><td class="r">{{ F::n($d['meninggal']) }}</td><td class="r">{{ F::n($d['pulang_paksa_aps']) }}</td></tr>
  @empty<tr><td colspan="5" class="mg-empty">Tidak ada data DPJP.</td></tr>@endforelse
  </tbody></table></div>
</div>
