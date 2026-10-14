@extends('layouts.site')
@section('title', 'Halaman tidak ditemukan')
@section('content')
<div class="page-head"><div class="wrap"><h1>Halaman tidak ditemukan</h1><p>Halaman yang Anda cari tidak tersedia atau sedang tidak aktif.</p></div></div>
<div class="section"><div class="wrap">
  <div class="card" style="max-width:640px">
    <p style="margin-top:0">Silakan kembali ke beranda, atau hubungi kami bila membutuhkan bantuan.</p>
    <ul class="contact-mini">@foreach($site['phones']->where('show_contact', true) as $ph)<li><i>☎</i><span>{{ $ph->label }} <b><a href="{{ $ph->href() }}">{{ $ph->number }}</a></b></span></li>@endforeach</ul>
    <p style="margin-bottom:0;margin-top:18px"><a class="btn blue" href="{{ route('home') }}">Kembali ke beranda</a></p>
  </div>
</div></div>
@endsection
