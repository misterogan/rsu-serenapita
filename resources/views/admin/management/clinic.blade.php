@use('App\Support\Fmt', 'F')
<h2 style="margin:0 0 12px">{{ $name }}</h2>
@include('admin.management._errors')
<div class="mg-grid">
  <div><h3 class="mg-sub">Dokter</h3>
    <table class="mg-tbl"><thead><tr><th>Dokter</th><th class="r">Pasien</th><th class="r">Baru</th></tr></thead><tbody>
    @forelse($doctors as $d)<tr><td>{{ $d['dokter'] }}</td><td class="r"><b>{{ F::n($d['pasien']) }}</b></td><td class="r">{{ F::n($d['baru']) }}</td></tr>
    @empty<tr><td colspan="3" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table></div>
  <div><h3 class="mg-sub">Diagnosa terbanyak</h3>
    <table class="mg-tbl"><thead><tr><th>Diagnosa</th><th class="r">Pasien</th></tr></thead><tbody>
    @forelse($diagnoses as $d)<tr><td><small class="code">{{ $d['kode'] }}</small> {{ $d['diagnosa'] }}</td><td class="r"><b>{{ F::n($d['total']) }}</b></td></tr>
    @empty<tr><td colspan="2" class="mg-empty">Tidak ada data.</td></tr>@endforelse
    </tbody></table></div>
</div>
