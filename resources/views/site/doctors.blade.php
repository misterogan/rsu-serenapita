@extends('layouts.site')
@php use App\Models\Doctor; @endphp
@section('title', 'Dokter spesialis di '.$site['seo']['city'])
@section('breadcrumb', 'Dokter')
@section('description', 'Daftar dokter umum dan dokter spesialis di '.$site['profile']['name'].', '.$site['seo']['city'].': '.$polis->pluck('name')->implode(', ').'. Lihat jadwal praktik dan buat janji online.')
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
    @include('site.partials.doctor-card', ['d' => $d])
    @endforeach
  </div>
</div></div>
@endsection
