@extends('layouts.site')
@section('title', $news->title)
@section('description', $news->excerpt)
@section('og_type', 'article')
@if($news->image)@section('og_image', asset('storage/'.$news->image))@endif
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / <a href="{{ route('news.index') }}">Berita</a></div><h1>{{ $news->title }}</h1><p>{{ $news->published_at->translatedFormat('l, d F Y') }} · {{ $news->category?->name }}</p></div></div>
<div class="section"><div class="wrap article">
  <div class="thumb">@include('partials.cover', ['news' => $news])</div>
  <p><strong>{{ $news->excerpt }}</strong></p>
  @foreach($news->paragraphs() as $para)<p>{{ $para }}</p>@endforeach
  @if($more->isNotEmpty())
  <h2 class="sec" style="margin-top:40px">Berita lainnya</h2>
  <ul class="dept-list">@foreach($more as $m)<li><a href="{{ route('news.show', $m->slug) }}">{{ $m->title }}<span aria-hidden="true">›</span></a></li>@endforeach</ul>
  @endif
</div></div>
@endsection
