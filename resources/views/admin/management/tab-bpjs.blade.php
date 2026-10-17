@use('App\Support\Fmt', 'F')
@include('admin.management._errors')
@php $sum = $cbg['summary']; $rows = collect($cbg['cbg']); $tot = ['tarif' => $sum ? collect($sum)->sum('tarif_inacbg') : 0, 'biaya' => collect($sum)->sum('biaya_rs')]; @endphp
<div class="card mg-card">
  @include('admin.management._head', ['title' => 'Tarif INA-CBG dibandingkan biaya rumah sakit', 'note' => 'Untuk pasien BPJS dengan SEP terbit pada periode ini. <b>Biaya rumah sakit</b> diambil dari billing Khanza. Hanya kasus yang sudah di-grouping dan sudah ada billing-nya yang dibandingkan. <b>Selisih positif</b> berarti tarif INA-CBG lebih besar dari biaya rumah sakit.'])
  <div class="table-scroll"><table class="mg-tbl"><thead><tr><th>Layanan</th><th class="r">SEP</th><th class="r">Sudah grouping</th><th class="r">Dibandingkan</th><th class="r">Tarif INA-CBG</th><th class="r">Biaya RS</th><th class="r">Selisih</th></tr></thead><tbody>
  @forelse($sum as $s)<tr><td><b>{{ $s['layanan'] }}</b></td><td class="r">{{ F::n($s['sep']) }}</td><td class="r">{{ F::n($s['sudah_grouping']) }}</td><td class="r">{{ F::n($s['dibandingkan']) }}</td>
    <td class="r">{{ F::rps($s['tarif_inacbg']) }}</td><td class="r">{{ F::rps($s['biaya_rs']) }}</td><td class="r"><span class="tag {{ $s['selisih'] >= 0 ? 'ok' : 'bad' }}">{{ F::rps($s['selisih']) }}</span></td></tr>
  @empty<tr><td colspan="7" class="mg-empty">Tidak ada SEP pada periode ini.</td></tr>@endforelse
  </tbody></table></div>
</div>
<div class="mg-grid">
  @foreach([['10 CBG dengan kerugian terbesar', $rows->where('selisih', '<', 0)->take(10)], ['10 CBG dengan keuntungan terbesar', $rows->where('selisih', '>=', 0)->reverse()->take(10)]] as [$title, $list])
  <div class="card mg-card">
    @include('admin.management._head', ['title' => $title, 'csv' => 'inacbg'])
    <div class="table-scroll"><table class="mg-tbl"><thead><tr><th>CBG</th><th class="r">Kasus</th><th class="r">Rata-rata selisih</th><th class="r">Total selisih</th></tr></thead><tbody>
    @forelse($list as $c)<tr><td><small class="code">{{ $c['cbg'] }}</small> {{ \Illuminate\Support\Str::limit($c['deskripsi'], 46) }}</td><td class="r">{{ F::n($c['kasus']) }}</td><td class="r">{{ F::rps($c['selisih'] / max(1, $c['kasus'])) }}</td><td class="r"><span class="tag {{ $c['selisih'] >= 0 ? 'ok' : 'bad' }}">{{ F::rps($c['selisih']) }}</span></td></tr>
    @empty<tr><td colspan="4" class="mg-empty">Belum ada kasus yang bisa dibandingkan.</td></tr>@endforelse
    </tbody></table></div>
  </div>
  @endforeach
</div>
