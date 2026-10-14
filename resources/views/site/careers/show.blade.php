@extends('layouts.site')
@section('title', $job->title.' – Karier')
@section('description', $job->summary ?: 'Lowongan '.$job->title.' di '.$site['profile']['name'].'.')
@section('content')
<div class="page-head"><div class="wrap"><div class="crumb"><a href="{{ route('home') }}">Beranda</a> / <a href="{{ route('careers.index') }}">Karier</a></div>
  <h1>{{ $job->title }}</h1>
  <p>{{ collect([$job->department, $job->employment_type, $job->location])->filter()->implode(' · ') }}</p>
</div></div>
<div class="section"><div class="wrap book">
  <div class="article" style="max-width:none">
    <div class="job-facts">
      <div><small>Status</small><b>{{ $job->isOpen() ? 'Menerima lamaran' : 'Ditutup' }}</b></div>
      <div><small>Batas lamaran</small><b>{{ $job->deadline ? $job->deadline->translatedFormat('d F Y') : 'Tanpa batas' }}</b></div>
      <div><small>Pendidikan minimal</small><b>{{ $job->education ?: '—' }}</b></div>
      <div><small>Dibutuhkan</small><b>{{ $job->quota ? $job->quota.' orang' : '—' }}</b></div>
    </div>
    @if($job->summary)<p><strong>{{ $job->summary }}</strong></p>@endif
    @if($job->description)<h2 class="sec" style="margin-top:28px">Deskripsi pekerjaan</h2>@foreach($job->paragraphs('description') as $para)<p>{{ $para }}</p>@endforeach @endif
    @if($job->requirements)<h2 class="sec" style="margin-top:28px">Persyaratan</h2><ul class="job-list">@foreach($job->lines('requirements') as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
    @if($job->benefits)<h2 class="sec" style="margin-top:28px">Yang kami tawarkan</h2><ul class="job-list">@foreach($job->lines('benefits') as $l)<li>{{ $l }}</li>@endforeach</ul>@endif
    <p style="margin-top:30px"><a href="{{ route('careers.index') }}" style="font-weight:700">← Lihat lowongan lainnya</a></p>
  </div>

  <aside>
    @if($applied)
    <div class="success" role="status">
      <p style="margin:0 0 6px;font-weight:700">Lamaran berhasil dikirim</p>
      <div class="code">{{ $applied }}</div>
      <p style="font-size:14px;color:var(--muted);margin-bottom:0">Simpan kode lamaran ini. Tim kami akan menghubungi Anda lewat email atau telepon bila lolos seleksi berkas.</p>
    </div>
    @endif

    @if($job->isOpen())
    <div class="card" id="lamar">
      <h2 class="sec">Kirim lamaran</h2>
      @if($errors->any())<div class="alert err" role="alert">Periksa kembali isian yang ditandai.</div>@endif
      <form method="post" action="{{ route('careers.apply', $job->slug) }}#lamar" enctype="multipart/form-data" novalidate>
        @csrf
        <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="field"><label for="aName">Nama lengkap</label><input id="aName" name="name" value="{{ old('name') }}" autocomplete="name" required>@error('name')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="aEmail">Email</label><input id="aEmail" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="aPhone">Nomor HP / WhatsApp</label><input id="aPhone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" required>@error('phone')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="row2">
          <div class="field"><label for="aBirth">Tanggal lahir</label><input id="aBirth" name="birth_date" type="date" value="{{ old('birth_date') }}" required>@error('birth_date')<div class="err-text">{{ $message }}</div>@enderror</div>
          <div class="field"><label for="aCity">Kota domisili</label><input id="aCity" name="city" value="{{ old('city') }}" required>@error('city')<div class="err-text">{{ $message }}</div>@enderror</div>
        </div>
        <div class="row2">
          <div class="field"><label for="aEdu">Pendidikan terakhir</label><select id="aEdu" name="education" required><option value="">Pilih</option>@foreach($education as $e)<option @selected(old('education') === $e)>{{ $e }}</option>@endforeach</select>@error('education')<div class="err-text">{{ $message }}</div>@enderror</div>
          <div class="field"><label for="aExp">Pengalaman kerja (tahun)</label><input id="aExp" name="experience_years" type="number" min="0" max="50" value="{{ old('experience_years', 0) }}" required>@error('experience_years')<div class="err-text">{{ $message }}</div>@enderror</div>
        </div>
        <div class="field"><label for="aMajor">Jurusan</label><input id="aMajor" name="major" value="{{ old('major') }}" placeholder="Contoh: Ilmu Keperawatan"></div>
        <div class="field"><label for="aInst">Asal sekolah / kampus</label><input id="aInst" name="institution" value="{{ old('institution') }}"></div>
        <div class="field"><label for="aCover">Surat lamaran singkat <span style="font-weight:400;color:var(--muted)">(opsional)</span></label><textarea id="aCover" name="cover_letter" rows="4" placeholder="Ceritakan singkat tentang diri dan pengalaman Anda.">{{ old('cover_letter') }}</textarea>@error('cover_letter')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="aCv">CV (PDF, maks. 2 MB)</label><input id="aCv" name="cv" type="file" accept="application/pdf" required>@error('cv')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="field"><label for="aDoc">Dokumen pendukung <span style="font-weight:400;color:var(--muted)">(opsional, PDF, maks. 5 MB)</span></label><input id="aDoc" name="doc" type="file" accept="application/pdf"><div class="hint">Gabungkan ijazah, transkrip, STR, dan sertifikat dalam satu file PDF.</div>@error('doc')<div class="err-text">{{ $message }}</div>@enderror</div>
        <div class="field"><label class="check"><input type="checkbox" name="consent" value="1" @checked(old('consent'))> Saya menyatakan data yang saya isi benar, dan menyetujui data ini disimpan {{ $site['profile']['name'] }} untuk keperluan rekrutmen.</label>@error('consent')<div class="err-text">{{ $message }}</div>@enderror</div>
        <button class="btn blue" type="submit" style="width:100%;text-align:center">Kirim lamaran</button>
      </form>
    </div>
    @else
    <div class="card"><h2 class="sec">Lowongan ditutup</h2><p style="margin:0;color:var(--muted)">Lowongan ini sudah tidak menerima lamaran. Lihat <a href="{{ route('careers.index') }}">lowongan lainnya</a>.</p></div>
    @endif
  </aside>
</div></div>
@endsection
