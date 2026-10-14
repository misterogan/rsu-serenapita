@extends('layouts.site')
@section('title', 'Jadwal dokter')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Jadwal</div><h1>Jadwal praktik dokter</h1><p>Jadwal 7 hari ke depan, diperbarui otomatis dari sistem rumah sakit. Dokter yang cuti ditandai.</p></div></div>
<div class="section"><div class="wrap">
  <div class="table-scroll"><table class="sched">
    <thead><tr><th>Dokter</th>@foreach($days as $d)<th>{{ $d->translatedFormat('D d/m') }}{{ $d->isToday() ? ' · hari ini' : '' }}</th>@endforeach<th></th></tr></thead>
    <tbody>
    @forelse($polis as $p)
      <tr class="group"><td colspan="9">{{ $p->name }}</td></tr>
      @foreach($p->doctors as $doc)
      <tr><th style="text-align:left;font-weight:600">{{ $doc->name }}</th>
        @foreach($days as $d)
          @php $slot = $doc->slotFor($d->dayOfWeek); @endphp
          @if($doc->onLeave($d))<td class="cuti">Cuti</td>@elseif($slot)<td class="on">{{ $slot[1] }}–{{ $slot[2] }}</td>@else<td class="off">—</td>@endif
        @endforeach
        <td>@if(\App\Support\Links::enabled('appointment'))<a href="{{ route('appointment', ['doctor' => $doc->id]) }}" style="font-weight:700;font-size:13px">Buat janji</a>@endif</td></tr>
      @endforeach
    @empty
      <tr><td colspan="9" class="empty">Jadwal belum tersedia.</td></tr>
    @endforelse
    </tbody>
  </table></div>
</div></div>
@endsection
