@extends('layouts.site')
@php use App\Models\Doctor; @endphp
@section('title', 'Buat janji temu')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / Buat Janji</div><h1>Buat janji temu dokter</h1><p>Janji temu langsung tercatat di sistem rumah sakit. Tunjukkan kode janji di loket pendaftaran.</p></div></div>
<div class="section"><div class="wrap book">
  <div>
    @if($booked)
    <div class="success" role="status">
      <p style="margin:0 0 6px;font-weight:700">Janji temu berhasil dibuat</p>
      <div class="code">{{ $booked->code }}</div>
      <p>{{ $booked->doctor->name }} · {{ $booked->doctor->poli?->name }}<br>{{ $booked->date->translatedFormat('l, d F Y') }}, pukul {{ $booked->time }}
        @if($booked->queue_number)<br>Nomor antrean: <b>{{ $booked->queue_number }}</b>@endif</p>
      @if(!$booked->simrs_sent_at)<p style="color:#C98418;font-weight:600">Janji Anda sudah kami terima, tetapi belum terhubung ke sistem rumah sakit. Petugas akan menghubungi Anda untuk konfirmasi.</p>@endif
      <p style="font-size:14px;color:var(--muted);margin-bottom:0">{{ $booking['note'] }}@if($booked->payment === 'BPJS Kesehatan' && $booking['rujukan']) Bawa kartu BPJS dan surat rujukan yang masih berlaku.@endif</p>
    </div>
    @endif

    @if(!$booking['online'])
      <div class="card"><h2 class="sec">Pendaftaran online sedang ditutup</h2><p>Silakan daftar melalui telepon atau datang ke loket pendaftaran.</p>
        <ul class="contact-mini">@foreach($site['phones']->where('show_contact', true) as $ph)<li><i>☎</i><span>{{ $ph->label }} <b><a href="{{ $ph->href() }}">{{ $ph->number }}</a></b></span></li>@endforeach</ul></div>
    @else
    <div class="card">
      @if($errors->any())<div class="alert err" role="alert">Periksa kembali isian yang ditandai.</div>@endif
      <form id="bookForm" method="post" action="{{ route('appointment.store') }}" novalidate>
        @csrf
        <div class="row2">
          <div class="field"><label for="bPoli">Poliklinik</label>
            @php $sel = old('doctor_id', $preselect); $selPoli = optional($polis->first(fn($p) => $p->doctors->contains('id', (int) $sel)))->id; @endphp
            <select id="bPoli">@foreach($polis as $p)<option value="{{ $p->id }}" @selected($selPoli === $p->id)>{{ $p->name }}</option>@endforeach</select></div>
          <div class="field"><label for="bDoc">Dokter</label>
            <select id="bDoc" name="doctor_id" data-selected="{{ $sel }}" required>
              @foreach($polis as $p)@foreach($p->doctors as $d)<option value="{{ $d->id }}" data-poli="{{ $p->id }}" data-days="{{ $d->scheduleText() }}">{{ $d->name }}</option>@endforeach @endforeach
            </select>
            @error('doctor_id')<div class="err-text">{{ $message }}</div>@enderror</div>
        </div>
        <div class="field"><label for="bDate">Tanggal kunjungan</label><input type="date" id="bDate" name="date" min="{{ $minDate }}" max="{{ $maxDate }}" value="{{ old('date') }}" required><div class="hint" id="dateHint"></div>@error('date')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="field"><label>Jam kedatangan</label><div class="slots" id="bSlots" data-old="{{ old('time') }}"><span class="empty">Pilih dokter dan tanggal terlebih dahulu.</span></div>@error('time')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="row2">
          <div class="field"><label for="bName">Nama lengkap pasien</label><input id="bName" name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<div class="err-text">{{ $message }}</div>@enderror</div>
          <div class="field"><label for="bPhone">Nomor HP / WhatsApp</label><input id="bPhone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required>@error('phone')<div class="err-text">{{ $message }}</div>@enderror</div>
        </div>
        <div class="row2">
          <div class="field"><label for="bBirth">Tanggal lahir</label><input id="bBirth" name="birth_date" type="date" value="{{ old('birth_date') }}" max="{{ today()->subDay()->toDateString() }}" required>@error('birth_date')<div class="err-text">{{ $message }}</div>@enderror</div>
          <div class="field"><label for="bNik">NIK @unless($booking['require_nik'])<span style="font-weight:400;color:var(--muted)">(opsional)</span>@endunless</label><input id="bNik" name="nik" inputmode="numeric" maxlength="16" value="{{ old('nik') }}">@error('nik')<div class="err-text">{{ $message }}</div>@enderror</div>
        </div>
        <div class="field"><label>Cara bayar</label><div class="radio-row">
          @foreach(['BPJS Kesehatan', 'Umum', 'Asuransi lain'] as $pay)<label><input type="radio" name="payment" value="{{ $pay }}" @checked(old('payment', 'BPJS Kesehatan') === $pay)> {{ $pay }}</label>@endforeach
        </div></div>
        <div class="row2 bpjs-only">
          <div class="field"><label for="bBpjs">Nomor kartu BPJS</label><input id="bBpjs" name="bpjs_number" inputmode="numeric" maxlength="13" value="{{ old('bpjs_number') }}">@error('bpjs_number')<div class="err-text">{{ $message }}</div>@enderror</div>
          <div class="field"><label for="bRuj">Nomor rujukan</label><input id="bRuj" name="referral_number" value="{{ old('referral_number') }}">@error('referral_number')<div class="err-text">{{ $message }}</div>@enderror</div>
        </div>
        <div class="field"><label for="bRM">Nomor rekam medis <span style="font-weight:400;color:var(--muted)">(jika pasien lama)</span></label><input id="bRM" name="medical_record" value="{{ old('medical_record') }}"></div>
        <div class="field"><label for="bNote">Keluhan singkat</label><textarea id="bNote" name="note" rows="3">{{ old('note') }}</textarea></div>
        <div class="field"><label class="check"><input type="checkbox" name="consent" value="1" @checked(old('consent'))> {{ $booking['consent'] }}</label>@error('consent')<div class="err-text">{{ $message }}</div>@enderror</div>
        <button class="btn blue" type="submit">Buat janji temu</button>
      </form>
    </div>
    @endif
  </div>
  <aside>
    <div class="card"><h2 class="sec">Janji temu Anda</h2>
      <ul class="myappt">@forelse($mine as $a)<li><b>{{ $a->code }} · {{ $a->doctor->name }}</b>{{ $a->date->translatedFormat('l, d F Y') }}, pukul {{ $a->time }}<br>Status: {{ $a->status }}</li>@empty<li class="empty">Janji yang Anda buat di perangkat ini akan muncul di sini.</li>@endforelse</ul>
    </div>
    <div class="card" style="margin-top:20px;font-size:14px;color:var(--muted)"><h2 class="sec">Yang perlu dibawa</h2><p style="margin:0 0 8px">KTP, kartu BPJS Kesehatan dan surat rujukan untuk pasien BPJS, serta kartu berobat bagi pasien lama.</p><p style="margin:0">Untuk keadaan darurat, jangan menunggu jadwal. Langsung ke IGD.</p></div>
  </aside>
</div></div>
@endsection
