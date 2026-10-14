@extends('layouts.site')
@php use App\Models\Doctor; @endphp
@section('title', 'Dokter')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Dokter</div><h1>Dokter kami</h1><p>Cari dokter berdasarkan nama atau poliklinik, lalu buat janji pada jam praktiknya.</p></div></div>
<div class="section"><div class="wrap">
  <form class="toolbar" method="get">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama dokter" aria-label="Cari nama dokter">
    <select name="clinic" aria-label="Filter poliklinik" onchange="this.form.submit()"><option value="">Semua poliklinik</option>@foreach($polis as $p)<option value="{{ $p->id }}" @selected(request('clinic') == $p->id)>{{ $p->name }}</option>@endforeach</select>
  </form>
  @if($doctors->isEmpty())<p class="empty">Tidak ada dokter yang cocok. Coba kata kunci lain atau pilih semua poliklinik.</p>@endif
  <div class="doc-grid">
    @foreach($doctors as $d)
    <div class="doc">
      <div class="avatar">@if($d->photo)<img src="{{ asset('storage/'.$d->photo) }}" alt="">@else<div style="background:{{ $d->color }}">{{ $d->initials() }}</div>@endif</div>
      <div class="info">
        <h3>{{ $d->name }}</h3><div class="spec">{{ $d->poli?->name }}</div>
        @if($leave = $d->leaves->first(fn($l) => $l->date_to->gte(today())))<div class="leave-note">Cuti {{ $leave->date_from->translatedFormat('d M') }} – {{ $leave->date_to->translatedFormat('d M Y') }}</div>@endif
        @if($d->bio)<p class="bio">{{ $d->bio }}</p>@endif
        <ul>@foreach($d->schedule ?? [] as $s)<li><span>{{ Doctor::DAYS[$s[0]] }}</span><span>{{ $s[1] }} – {{ $s[2] }}</span></li>@endforeach</ul>
        @if(\App\Support\Links::enabled('appointment'))<a class="btn ghost" href="{{ route('appointment', ['doctor' => $d->id]) }}">Buat janji</a>@endif
      </div>
    </div>
    @endforeach
  </div>
</div></div>
@endsection
