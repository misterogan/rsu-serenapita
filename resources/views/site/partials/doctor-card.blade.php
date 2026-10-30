<div class="doc">
  <div class="avatar">@if($d->photo)<img src="{{ asset('storage/'.$d->photo) }}" alt="{{ $d->name }}" loading="lazy" style="{{ $d->photoStyle() }}">@else<div style="background:{{ $d->color }}">{{ $d->initials() }}</div>@endif</div>
  <div class="info">
    <h3>{{ $d->name }}</h3>
    <div class="spec">@if($d->poli && empty($hidePoli))<a href="{{ route('clinics.show', $d->poli->slug()) }}">{{ $d->poli->name }}</a>@elseif($d->poli){{ $d->poli->name }}@endif</div>
    @if($leave = $d->leaves->first(fn($l) => $l->date_to->gte(today())))<div class="leave-note">Cuti {{ $leave->date_from->translatedFormat('d M') }} – {{ $leave->date_to->translatedFormat('d M Y') }}</div>@endif
    @if($d->bio)<div class="bio rich">{!! \App\Support\Rich::render($d->bio) !!}</div>@endif
    <ul>@foreach(collect($d->schedule ?? [])->sortBy(fn ($s) => (($s[0] + 6) % 7).$s[1]) as $s)<li><span>{{ \App\Models\Doctor::DAYS[$s[0]] }}</span><span>{{ $s[1] }} – {{ $s[2] }}</span></li>@endforeach</ul>
    @if(\App\Support\Links::enabled('appointment'))<a class="btn ghost" href="{{ route('appointment', ['doctor' => $d->id]) }}">Buat janji</a>@endif
  </div>
</div>
