@extends('layouts.site')
@section('title', 'Berita dan informasi kesehatan')
@section('breadcrumb', 'Berita')
@section('description', 'Berita kegiatan, pengumuman layanan, dan artikel kesehatan dari '.$site['profile']['name'].', '.$site['seo']['city'].'.')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Berita</div><h1>Berita dan informasi kesehatan</h1><p>Kabar kegiatan rumah sakit, pengumuman layanan, dan tips kesehatan.</p></div></div>
<div class="section"><div class="wrap">
  <div class="filter"><a class="chip {{ !$active ? 'on' : '' }}" href="{{ route('news.index') }}">Semua</a>@foreach($categories as $c)<a class="chip {{ $active === $c ? 'on' : '' }}" href="{{ route('news.index', ['category' => $c]) }}">{{ $c }}</a>@endforeach</div>
  @if($news->isEmpty())<p class="empty">Belum ada berita di kategori ini.</p>@endif
  <div class="news-grid wide">@foreach($news as $n)@include('partials.post', ['n' => $n])@endforeach</div>
  {{ $news->links() }}
</div></div>
@endsection
