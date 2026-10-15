@extends('layouts.admin', ['current' => 'schedule'])
@section('title', 'Jadwal dokter')
@section('content')
@php $manualMode = \App\Support\DataSource::manual(); @endphp
<x-admin-head module="schedule" :desc="$manualMode ? 'Jadwal 7 hari ke depan beserta cuti dokter. Jadwal diubah di menu Profil dokter, dan cuti di menu Cuti dokter.' : 'Jadwal 7 hari ke depan beserta cuti dokter, langsung dari SIMRS. Website tidak membuat jadwal sendiri agar tidak terjadi data ganda.'">
  @if($manualMode)
    @if(auth()->user()->canModule('doctors'))<a class="btn" href="{{ route('admin.crud.index', 'doctors') }}">Ubah jadwal dokter</a>@endif
    @if(auth()->user()->canModule('leaves'))<a class="btn pri" href="{{ route('admin.crud.create', 'leaves') }}">+ Catat cuti</a>@endif
  @else
    @if(auth()->user()->canModule('simrs'))<form method="post" action="{{ route('admin.simrs.sync') }}" class="inline">@csrf<button class="btn">Sinkronkan sekarang</button></form>@endif
  @endif
</x-admin-head>
@unless($manualMode)<x-sync-note />@endunless
<div class="card" style="margin-bottom:20px"><div class="card-h"><h2>Jadwal minggu ini</h2></div>
  <div class="table-scroll"><table class="tbl sched-mx" style="min-width:900px"><thead><tr><th>Dokter</th>@foreach($days as $d)<th>{{ $d->translatedFormat('D d') }}{{ $d->isToday() ? ' · hari ini' : '' }}</th>@endforeach</tr></thead><tbody>
  @foreach($polis as $p)
    @continue($p->doctors->isEmpty())
    <tr><td colspan="8" style="background:var(--soft);color:var(--blue);font-weight:700">{{ $p->name }}</td></tr>
    @foreach($p->doctors as $doc)
    <tr><td><div class="cellrow"><div class="av" style="background:{{ $doc->color }};width:28px;height:28px">{{ $doc->initials() }}</div><span class="main">{{ $doc->name }}</span></div></td>
      @foreach($days as $d)@php $hours = $doc->hoursText($d->dayOfWeek); @endphp
        @if($doc->onLeave($d))<td class="cuti">Cuti</td>@elseif($hours)<td class="on">{!! str_replace(', ', '<br>', e($hours)) !!}</td>@else<td class="off">—</td>@endif
      @endforeach</tr>
    @endforeach
  @endforeach
  </tbody></table></div></div>
<div class="card"><div class="card-h"><h2>Cuti dan libur dokter</h2></div>
  <div class="table-scroll"><table class="tbl"><thead><tr><th>Dokter</th><th>Periode</th><th>Keterangan</th><th>Status</th></tr></thead><tbody>
  @forelse($leaves as $l)
    <tr><td><span class="main">{{ $l->doctor?->name }}</span></td><td>{{ $l->date_from->translatedFormat('d M Y') }} – {{ $l->date_to->translatedFormat('d M Y') }}</td><td>{{ $l->reason }}</td>
      <td>@if($l->date_to->lt(today()))<span class="badge b-grey">Selesai</span>@elseif($l->date_from->lte(today()))<span class="badge b-amber">Berlangsung</span>@else<span class="badge b-blue">Akan datang</span>@endif</td></tr>
  @empty<tr><td colspan="4"><div class="empty">Tidak ada data cuti dari SIMRS.</div></td></tr>@endforelse
  </tbody></table></div></div>
@endsection
