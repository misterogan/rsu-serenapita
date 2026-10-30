@extends('layouts.site')
@section('title', 'Pengaduan & Saran')
@section('description', 'Sampaikan pengaduan, saran, pujian, atau pertanyaan kepada '.$site['profile']['name'].'.')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Pengaduan &amp; Saran</div><h1>Pengaduan &amp; saran</h1><p>Masukan Anda membantu kami memperbaiki pelayanan. Setiap laporan mendapat nomor tiket untuk memantau tanggapan kami.</p></div></div>
<div class="section"><div class="wrap book">
  <div>
    @if($sent)
    <div class="success" role="status">
      <p style="margin:0 0 6px;font-weight:700">Terima kasih, laporan Anda sudah kami terima</p>
      <div class="code">{{ $sent }}</div>
      <p style="font-size:14px;color:var(--muted);margin-bottom:0">Simpan nomor tiket ini. Cek tanggapan kami kapan saja lewat kotak <b>Cek status laporan</b>, dengan nomor tiket dan nomor HP yang sama.</p>
    </div>
    @endif
    <div class="card">
      <h2 class="sec">Kirim laporan</h2>
      @if($errors->any())<div class="alert err" role="alert">Periksa kembali isian yang ditandai.</div>@endif
      <form method="post" action="{{ route('feedback.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="field"><label>Jenis laporan</label>
          <div class="fb-types">@foreach($types as $t)<label class="fb-type"><input type="radio" name="type" value="{{ $t }}" @checked(old('type', 'Pengaduan') === $t)><span>{{ $t }}</span></label>@endforeach</div>
          @error('type')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="row2">
          <div class="field"><label for="fCat">Kategori</label><select id="fCat" name="category"><option value="">Pilih kategori</option>@foreach($categories as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach</select>@error('category')<div class="err-text">{{ $message }}</div>@enderror</div>
          <div class="field"><label for="fUnit">Unit atau ruangan <span class="opt">(opsional)</span></label><input id="fUnit" name="unit" value="{{ old('unit') }}" placeholder="Contoh: Poli Anak, IGD, Kasir"></div>
        </div>
        <div class="field"><label for="fMsg">Isi laporan</label><textarea id="fMsg" name="message" rows="6" placeholder="Ceritakan apa yang terjadi, kapan, dan di mana. Untuk saran, jelaskan perbaikan yang Anda harapkan.">{{ old('message') }}</textarea>@error('message')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="row2">
          <div class="field"><label for="fDate">Tanggal kejadian <span class="opt">(opsional)</span></label><input id="fDate" type="date" name="incident_date" value="{{ old('incident_date') }}" max="{{ today()->toDateString() }}">@error('incident_date')<div class="err-text">{{ $message }}</div>@enderror</div>
          <div class="field"><label for="fFile">Lampiran <span class="opt">(opsional)</span></label><input id="fFile" type="file" name="attachment" accept="image/jpeg,image/png,application/pdf"><div class="hint">Foto atau PDF, maks. 3 MB.</div>@error('attachment')<div class="err-text">{{ $message }}</div>@enderror</div>
        </div>
        <div class="row2">
          <div class="field"><label for="fName">Nama</label><input id="fName" name="name" value="{{ old('name') }}" autocomplete="name">@error('name')<div class="err-text">{{ $message }}</div>@enderror</div>
          <div class="field"><label for="fPhone">Nomor HP / WhatsApp</label><input id="fPhone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">@error('phone')<div class="err-text">{{ $message }}</div>@enderror</div>
        </div>
        <div class="field"><label for="fEmail">Email <span class="opt">(opsional)</span></label><input id="fEmail" type="email" name="email" value="{{ old('email') }}">@error('email')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="field"><label class="check"><input type="checkbox" name="consent" value="1" @checked(old('consent'))> Data saya hanya digunakan untuk menindaklanjuti laporan ini dan tidak dipublikasikan.</label>@error('consent')<div class="err-text">{{ $message }}</div>@enderror</div>
        <button class="btn blue" type="submit">Kirim laporan</button>
      </form>
    </div>
  </div>

  <aside>
    <div class="card" id="cek">
      <h2 class="sec">Cek status laporan</h2>
      <form method="get" action="{{ route('feedback.status') }}#cek" data-noload>
        <div class="field"><label for="cCode">Nomor tiket</label><input id="cCode" name="code" value="{{ request('code') }}" placeholder="ADU-XXXXXX" style="text-transform:uppercase"></div>
        <div class="field"><label for="cPhone">Nomor HP yang dipakai</label><input id="cPhone" name="phone" type="tel" value="{{ request('phone') }}"></div>
        <button class="btn ghost" type="submit">Cek status</button>
      </form>
      @if(!empty($checked))
        @if(!empty($ticket))
        <div class="fb-status">
          <div class="fb-row"><span>Tiket</span><b>{{ $ticket->code }}</b></div>
          <div class="fb-row"><span>Dikirim</span><b>{{ $ticket->created_at->translatedFormat('d M Y H.i') }}</b></div>
          <div class="fb-row"><span>Jenis</span><b>{{ $ticket->type }} · {{ $ticket->category }}</b></div>
          <div class="fb-row"><span>Status</span><b class="fb-st st-{{ strtolower($ticket->status) }}">{{ $ticket->status }}</b></div>
          @if($ticket->response)
          <div class="fb-resp"><small>Tanggapan kami · {{ $ticket->responded_at?->translatedFormat('d M Y H.i') }}</small><div class="rich">{!! \App\Support\Text::html($ticket->response) !!}</div></div>
          @else
          <p class="fb-wait">Laporan Anda sedang kami tinjau. Tanggapan akan tampil di sini.</p>
          @endif
        </div>
        @else
        <div class="alert err" role="alert" style="margin-top:14px">Laporan tidak ditemukan. Periksa nomor tiket dan nomor HP Anda.</div>
        @endif
      @endif
    </div>
    <div class="card" style="margin-top:20px">
      <h2 class="sec">Butuh bantuan segera?</h2>
      <ul class="contact-mini">@foreach($site['phones']->where('show_contact', true) as $ph)<li><i>☎</i><span>{{ $ph->label }} <b><a href="{{ $ph->href() }}">{{ $ph->number }}</a></b></span></li>@endforeach</ul>
    </div>
  </aside>
</div></div>
@endsection
