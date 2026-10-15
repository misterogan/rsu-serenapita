@extends('layouts.admin', ['current' => 'help'])
@section('title', 'Panduan')
@section('content')
<div class="vh">
  <div>
    <div class="crumb">Bantuan / Panduan</div>
    <h1>Panduan backoffice</h1>
    <p>Cara memakai setiap menu, langkah demi langkah. Ketik kata kunci untuk mencari, misalnya <i>foto dokter</i>, <i>sembunyikan menu</i>, atau <i>kata sandi</i>.</p>
  </div>
  <div class="vh-act"><button class="btn sm" type="button" onclick="window.print()">Cetak / simpan PDF</button></div>
</div>

<div class="help">
  <aside class="help-toc" aria-label="Daftar isi">
    <div class="help-search">
      <svg class="i" viewBox="0 0 24 24"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4"/></svg>
      <input type="search" id="helpQ" placeholder="Cari di panduan…  ( / )" aria-label="Cari di panduan" autocomplete="off">
    </div>
    <div class="help-count" id="helpCount" aria-live="polite"></div>
    <nav id="helpToc">
      @foreach($chapters as $c)<a href="#{{ $c['id'] }}" data-toc="{{ $c['id'] }}"><span>{{ $c['title'] }}</span><em></em></a>@endforeach
    </nav>
  </aside>
  <div class="help-body" id="helpBody">
    <div class="help-empty card" id="helpEmpty" hidden><b>Tidak ditemukan.</b> Coba kata kunci lain yang lebih umum, misalnya nama menu seperti <i>berita</i>, <i>dokter</i>, atau <i>live chat</i>.</div>
    @foreach($chapters as $c)
      <section class="doc card" data-chapter="{{ $c['id'] }}">{!! $c['html'] !!}</section>
    @endforeach
    @if($updated)<p class="help-updated">Panduan diperbarui {{ \Illuminate\Support\Carbon::createFromTimestamp($updated)->translatedFormat('d F Y') }}.</p>@endif
  </div>
</div>
@push('scripts')
<script src="{{ asset('js/help.js') }}?v=2"></script>
@endpush
@endsection
