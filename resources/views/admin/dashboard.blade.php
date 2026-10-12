@extends('layouts.admin', ['current' => 'dashboard'])
@section('title', 'Dasbor')
@php $h = now()->hour; $greet = $h < 11 ? 'Selamat pagi' : ($h < 15 ? 'Selamat siang' : ($h < 19 ? 'Selamat sore' : 'Selamat malam')); @endphp
@section('content')
<div class="vh"><div><div class="crumb">{{ now()->translatedFormat('l, d F Y') }}</div><h1>{{ $greet }}, {{ explode(' ', auth()->user()->name)[0] }}</h1>
  <p>Ringkasan pelayanan dan konten situs hari ini. <span class="pulse {{ $simrs['status'] === 'Gagal' ? 'bad' : '' }}"></span>SIMRS {{ strtolower($simrs['status']) }}@if($simrs['last_sync']), sinkron {{ \Illuminate\Support\Carbon::parse($simrs['last_sync'])->diffForHumans() }}@endif.</p></div></div>
<div class="stats">
  <a class="stat s1" href="{{ route('admin.crud.index', 'appointments') }}?date={{ today()->toDateString() }}"><span>Janji temu hari ini</span><b>{{ $stats['today'] }}</b><small>{{ $stats['present'] }} pasien sudah hadir</small></a>
  <a class="stat s2" href="{{ route('admin.livechat.index') }}"><span>Live chat menunggu</span><b>{{ $stats['chats'] }}</b><small>{{ $stats['chatsActive'] }} percakapan aktif</small></a>
  <a class="stat s3" href="{{ route('admin.crud.index', 'doctors') }}"><span>Dokter aktif</span><b>{{ $stats['doctors'] }}</b><small>dari {{ $stats['doctorsAll'] }} dokter di SIMRS</small></a>
  <a class="stat s4" href="{{ route('admin.crud.index', 'news') }}"><span>Berita terbit</span><b>{{ $stats['news'] }}</b><small>{{ $stats['drafts'] }} draf belum terbit</small></a>
</div>
<div class="quick">
  @if(auth()->user()->canModule('news'))<a class="btn" href="{{ route('admin.crud.create', 'news') }}">Tulis berita</a>@endif
  @if(auth()->user()->canModule('livechat'))<a class="btn" href="{{ route('admin.livechat.index') }}">Buka live chat</a>@endif
  @if(auth()->user()->canModule('simrs'))<form method="post" action="{{ route('admin.simrs.sync') }}" class="inline">@csrf<button class="btn">Sinkronkan SIMRS</button></form>@endif
  @if(auth()->user()->canModule('menu'))<a class="btn" href="{{ route('admin.menu') }}">Atur menu situs</a>@endif
</div>
<div class="g3">
  <div class="card"><div class="card-h"><div><h2>Janji temu terdekat</h2><p>Belum selesai dan tidak batal</p></div><a class="btn sm" href="{{ route('admin.crud.index', 'appointments') }}">Semua janji</a></div>
    <div class="table-scroll"><table class="tbl"><thead><tr><th>Pasien</th><th>Dokter</th><th>Waktu</th><th>Status</th></tr></thead><tbody>
    @forelse($upcoming as $a)<tr><td><span class="main">{{ $a->name }}</span><span class="sub">{{ $a->code }}</span></td><td>{{ $a->doctor?->name }}</td><td>{{ $a->date->translatedFormat('D, d M') }}<span class="sub">{{ $a->time }}</span></td><td>{!! \App\Admin\Resources::badge($a->status) !!}</td></tr>
    @empty<tr><td colspan="4"><div class="empty">Tidak ada janji temu mendatang.</div></td></tr>@endforelse
    </tbody></table></div></div>
  <div class="card"><div class="card-h"><div><h2>Janji per poliklinik</h2><p>7 hari ke depan</p></div></div><div class="card-b"><div class="bars">
    @forelse($perPoli as $x)<div class="bar-row"><span>{{ str_replace('Poli ', '', $x[0]) }}</span><div class="tr"><i style="width:{{ $x[1] / $max * 100 }}%"></i></div><b>{{ $x[1] }}</b></div>
    @empty<div class="empty">Belum ada janji minggu ini.</div>@endforelse
  </div></div></div>
</div>
<div class="g2" style="margin-top:20px">
  <div class="card"><div class="card-h"><div><h2>Cuti dokter</h2><p>Dari SIMRS</p></div><a class="btn sm" href="{{ route('admin.schedule') }}">Lihat jadwal</a></div><div class="card-b"><ul class="feed">
    @forelse($leaves as $l)<li><div class="av" style="background:{{ $l->doctor?->color }}">{{ $l->doctor?->initials() }}</div><div><b>{{ $l->doctor?->name }}</b><time>{{ $l->date_from->translatedFormat('d M') }} – {{ $l->date_to->translatedFormat('d M Y') }} · {{ $l->reason }}</time></div></li>
    @empty<li class="empty">Tidak ada dokter yang cuti.</li>@endforelse</ul></div></div>
  <div class="card"><div class="card-h"><div><h2>Aktivitas terbaru</h2></div>@if(auth()->user()->canModule('logs'))<a class="btn sm" href="{{ route('admin.logs') }}">Log lengkap</a>@endif</div><div class="card-b"><ul class="feed">
    @foreach($logs as $l)<li><div><b>{{ $l->user_name }}</b> {{ $l->action }}<time>{{ $l->created_at->translatedFormat('d M H.i') }}</time></div></li>@endforeach</ul></div></div>
</div>
@endsection
