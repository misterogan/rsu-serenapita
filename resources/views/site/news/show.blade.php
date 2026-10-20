@extends('layouts.site')
@section('title', $news->title)
@section('breadcrumb_parent', 'Berita|'.route('news.index'))
@section('description', $news->excerpt)
@section('og_type', 'article')
@if($news->image)@section('og_image', asset('storage/'.$news->image))@endif
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / @if(\App\Support\Links::enabled('news'))<a href="{{ route('news.index') }}">Berita</a>@else Berita @endif</div><h1>{{ $news->title }}</h1><p>{{ $news->published_at->translatedFormat('l, d F Y') }} · {{ $news->category?->name }}</p></div></div>
<div class="section"><div class="wrap article">
  @if($news->image)
  <a class="thumb photo" href="{{ asset('storage/'.$news->image) }}" target="_blank" rel="noopener" title="Buka gambar ukuran penuh"><img src="{{ asset('storage/'.$news->image) }}" alt="{{ $news->title }}"></a>
  @else
  <div class="thumb">@include('partials.cover', ['news' => $news])</div>
  @endif
  <p><strong>{{ $news->excerpt }}</strong></p>
  @foreach($news->paragraphs() as $para)<p>{{ $para }}</p>@endforeach
  @if($more->isNotEmpty())
  <h2 class="sec" style="margin-top:40px">Berita lainnya</h2>
  <ul class="dept-list">@foreach($more as $m)<li><a href="{{ route('news.show', $m->slug) }}">{{ $m->title }}<span aria-hidden="true">›</span></a></li>@endforeach</ul>
  @endif
</div></div>
@php
  $art = array_filter([
    '@context' => 'https://schema.org', '@type' => 'NewsArticle', 'headline' => \Illuminate\Support\Str::limit($news->title, 110, ''),
    'description' => $news->excerpt ?: null, 'image' => $news->image ? [asset('storage/'.$news->image)] : null,
    'datePublished' => optional($news->published_at)->toDateString() ?: $news->created_at->toIso8601String(),
    'dateModified' => $news->updated_at->toIso8601String(), 'mainEntityOfPage' => url()->current(),
    'author' => ['@type' => 'Organization', 'name' => $site['profile']['name'], 'url' => url('/')],
    'publisher' => ['@type' => 'Organization', 'name' => $site['profile']['name'], 'logo' => ['@type' => 'ImageObject', 'url' => $site['logo'] ?? asset('images/og-default.jpg')]],
  ]);
@endphp
<script type="application/ld+json">{!! json_encode($art, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection
