@extends('layouts.site')
@section('title', 'Karier')
@section('description', 'Lowongan kerja di '.$site['profile']['name'].'. Lihat posisi yang dibuka dan kirim lamaran secara online.')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Karier</div><h1>Karier di {{ $site['profile']['name'] }}</h1><p>Bergabunglah bersama tim kami melayani kesehatan masyarakat Dairi. Pilih lowongan yang sesuai, lalu kirim lamaran secara online.</p></div></div>
<div class="section"><div class="wrap">
  @if($departments->isNotEmpty() || request('type'))
  <form class="toolbar" method="get" data-noload>
    <select name="department" aria-label="Unit" onchange="this.form.submit()"><option value="">Semua unit</option>@foreach($departments as $d)<option value="{{ $d }}" @selected(request('department') === $d)>{{ $d }}</option>@endforeach</select>
    <select name="type" aria-label="Jenis pekerjaan" onchange="this.form.submit()"><option value="">Semua jenis pekerjaan</option>@foreach($types as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ $t }}</option>@endforeach</select>
  </form>
  @endif

  @forelse($jobs as $job)
  <a class="job" href="{{ route('careers.show', $job->slug) }}">
    <div class="job-main">
      <div class="job-tags">
        @if($job->isOpen())<span class="tag open">Dibuka</span>@else<span class="tag closed">Ditutup</span>@endif
        <span class="tag">{{ $job->employment_type }}</span>
        @if($job->department)<span class="tag">{{ $job->department }}</span>@endif
      </div>
      <h3>{{ $job->title }}</h3>
      @if($job->summary)<p>{{ $job->summary }}</p>@endif
      <div class="job-meta">
        <span>📍 {{ $job->location }}</span>
        @if($job->education)<span>🎓 Min. {{ $job->education }}</span>@endif
        @if($job->quota)<span>👥 {{ $job->quota }} orang</span>@endif
        <span>⏳ {{ $job->deadline ? 'Batas '.$job->deadline->translatedFormat('d F Y') : 'Tanpa batas waktu' }}</span>
      </div>
    </div>
    <span class="btn {{ $job->isOpen() ? 'blue' : 'ghost' }}">{{ $job->isOpen() ? 'Lihat & lamar' : 'Lihat detail' }}</span>
  </a>
  @empty
  <div class="card empty-state">
    <h2 class="sec">Belum ada lowongan</h2>
    <p>Saat ini belum ada lowongan yang dibuka. Silakan kunjungi halaman ini kembali secara berkala.</p>
  </div>
  @endforelse
</div></div>
@endsection
