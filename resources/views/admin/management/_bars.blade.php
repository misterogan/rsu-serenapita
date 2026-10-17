{{-- Grafik batang bulanan. Param: rows, label (kunci bulan), series [[kunci, nama, kelas]], money (bool) --}}
@use('App\Support\Fmt', 'F')
@php
  $W = 900; $H = 230; $pad = !empty($money) ? 74 : 46; $n = max(1, count($rows));
  $max = max(1, collect($rows)->map(fn ($r) => collect($series)->sum(fn ($s) => (float) ($r[$s[0]] ?? 0)))->max());
  $bw = ($W - 2 * $pad) / $n * 0.62; $step = ($W - 2 * $pad) / $n;
  $fmt = fn ($v) => !empty($money) ? F::rps($v) : F::n($v);
@endphp
@if(count($rows))
<svg class="mg-bars" viewBox="0 0 {{ $W }} {{ $H }}" role="img" aria-label="Grafik bulanan">
  @foreach([0, .5, 1] as $g)<line x1="{{ $pad }}" x2="{{ $W - $pad / 2 }}" y1="{{ $H - 26 - $g * ($H - 56) }}" y2="{{ $H - 26 - $g * ($H - 56) }}" class="grid"/><text x="{{ $pad - 6 }}" y="{{ $H - 22 - $g * ($H - 56) }}" class="ax" text-anchor="end">{{ !empty($money) ? F::rps($max * $g) : round($max * $g) }}</text>@endforeach
  @foreach(collect($rows)->values() as $i => $r)
    @php $x = $pad + $i * $step + ($step - $bw) / 2; $y0 = $H - 26; @endphp
    @foreach($series as [$k, $nm, $cls])
      @php $h = ((float) ($r[$k] ?? 0)) / $max * ($H - 56); $y0 -= $h; @endphp
      <rect x="{{ round($x, 1) }}" y="{{ round($y0, 1) }}" width="{{ round($bw, 1) }}" height="{{ round(max(0, $h), 1) }}" class="{{ $cls }}"><title>{{ F::month($r[$label]) }} · {{ $nm }}: {{ $fmt($r[$k] ?? 0) }}</title></rect>
    @endforeach
    <text x="{{ round($x + $bw / 2, 1) }}" y="{{ $H - 8 }}" class="ax" text-anchor="middle">{{ F::month($r[$label]) }}</text>
  @endforeach
</svg>
@if(count($series) > 1)<div class="mg-legend">@foreach($series as [$k, $nm, $cls])<span class="k-{{ $cls }}">{{ $nm }}</span>@endforeach</div>@endif
@else
<p class="mg-empty">Tidak ada data.</p>
@endif
