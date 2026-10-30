@extends('layouts.admin', ['current' => 'about'])
@section('title', 'Tentang kami')
@php $img = fn ($k) => !empty($a[$k]) ? asset('storage/'.$a[$k]) : null; @endphp
@section('content')
<form method="post" enctype="multipart/form-data">
@csrf
<x-admin-head module="about" desc="Isi halaman Tentang Kami di situs: profil singkat, sejarah, visi dan misi, akreditasi, penghargaan, sambutan direktur, dan struktur organisasi. Bagian yang dikosongkan tidak ditampilkan.">
  @if(\App\Support\Links::enabled('about'))<a class="btn" href="{{ route('about') }}" target="_blank">Lihat halaman</a>@endif
  <button class="btn pri">Simpan</button>
</x-admin-head>
<div style="display:flex;flex-direction:column;gap:20px">
  <div class="card"><div class="card-h"><div><h2>Profil singkat</h2><p>Tampil paling atas di halaman Tentang Kami.</p></div></div><div class="card-b">
    <div class="form" style="margin-bottom:16px">
      <div class="fld"><label for="intro">Ringkasan</label><textarea id="intro" name="intro" rows="3">{{ old('intro', $a['intro']) }}</textarea><div class="hint">2 sampai 3 kalimat tentang rumah sakit. Maks. 600 karakter.</div></div>
      <div class="fld"><label for="history">Sejarah</label><textarea id="history" name="history" rows="6">{{ old('history', $a['history']) }}</textarea><div class="hint">Pisahkan paragraf dengan satu baris kosong. Baris yang diawali • atau - tampil sebagai daftar poin, dan teks sebelum titik dua ditebalkan.</div></div>
    </div>
    <div class="logo-slots">
      @foreach([['image', 'Foto rumah sakit', 'Foto gedung atau suasana. Ideal 1600 × 900 px, maks. 3 MB.'], ['accreditation_image', 'Sertifikat akreditasi', 'Foto atau pindaian sertifikat. Maks. 3 MB.']] as [$k, $t, $d])
      <div class="logo-slot"><div class="logo-prev light" id="prev_{{ $k }}">@if($img($k))<img src="{{ $img($k) }}" alt="">@else<span style="color:var(--muted);font-size:12px">Belum ada</span>@endif</div>
        <div><b>{{ $t }}</b><small>{{ $d }}</small><input type="file" name="{{ $k }}" accept="image/png,image/jpeg,image/webp" data-preview="#prev_{{ $k }}" aria-label="Unggah {{ $t }}">
          @if(!empty($a[$k]))<label style="display:block;font-size:12px;margin-top:6px"><input type="checkbox" name="{{ $k }}_remove" value="1"> Hapus gambar</label>@endif</div></div>
      @endforeach
    </div>
  </div></div>

  <div class="card"><div class="card-h"><div><h2>Visi, misi, dan nilai</h2><p>Untuk misi dan nilai, tulis satu poin per baris.</p></div></div><div class="card-b form">
    <div class="fld"><label for="vision">Visi</label><textarea id="vision" name="vision" rows="2">{{ old('vision', $a['vision']) }}</textarea></div>
    <div class="fld half"><label for="mission">Misi</label><textarea id="mission" name="mission" rows="6" placeholder="Satu misi per baris">{{ old('mission', $a['mission']) }}</textarea></div>
    <div class="fld half"><label for="values">Nilai atau moto</label><textarea id="values" name="values" rows="6" placeholder="Satu nilai per baris, misalnya: Ramah – melayani dengan senyum">{{ old('values', $a['values']) }}</textarea></div>
  </div></div>

  <div class="card"><div class="card-h"><div><h2>Akreditasi dan penghargaan</h2></div></div><div class="card-b form">
    <div class="fld"><label for="accreditation">Status akreditasi</label><input id="accreditation" name="accreditation" value="{{ old('accreditation', $a['accreditation']) }}" placeholder="Contoh: Terakreditasi Paripurna oleh KARS, 2024"></div>
    <div class="fld"><label for="awards">Penghargaan</label><textarea id="awards" name="awards" rows="4" placeholder="Satu penghargaan per baris">{{ old('awards', $a['awards']) }}</textarea></div>
  </div></div>

  <div class="card"><div class="card-h"><div><h2>Sambutan direktur</h2></div></div><div class="card-b">
    <div class="form" style="margin-bottom:16px">
      <div class="fld half"><label for="director_name">Nama direktur</label><input id="director_name" name="director_name" value="{{ old('director_name', $a['director_name']) }}"></div>
      <div class="fld half"><label for="director_title">Jabatan</label><input id="director_title" name="director_title" value="{{ old('director_title', $a['director_title']) }}"></div>
      <div class="fld"><label for="director_message">Kata sambutan</label><textarea id="director_message" name="director_message" rows="5">{{ old('director_message', $a['director_message']) }}</textarea><div class="hint">Pisahkan paragraf dengan satu baris kosong.</div></div>
    </div>
    <div class="logo-slots">
      @foreach([['director_photo', 'Foto direktur', 'Persegi, ideal 600 × 600 px, maks. 2 MB.'], ['org_chart', 'Struktur organisasi', 'Gambar bagan organisasi. PNG atau JPG, maks. 4 MB.']] as [$k, $t, $d])
      <div class="logo-slot"><div class="logo-prev light" id="prev_{{ $k }}">@if($img($k))<img src="{{ $img($k) }}" alt="">@else<span style="color:var(--muted);font-size:12px">Belum ada</span>@endif</div>
        <div><b>{{ $t }}</b><small>{{ $d }}</small><input type="file" name="{{ $k }}" accept="image/png,image/jpeg,image/webp" data-preview="#prev_{{ $k }}" aria-label="Unggah {{ $t }}">
          @if(!empty($a[$k]))<label style="display:block;font-size:12px;margin-top:6px"><input type="checkbox" name="{{ $k }}_remove" value="1"> Hapus gambar</label>@endif</div></div>
      @endforeach
    </div>
  </div></div>
</div>
</form>
@endsection
