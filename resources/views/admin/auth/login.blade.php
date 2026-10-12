@php $p = \App\Support\SiteSettings::profile(); $logo = \App\Support\SiteSettings::logoUrl(true); @endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
<title>Masuk · Backoffice {{ $p['name'] }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=1">
</head>
<body>
<div class="login">
  <div class="login-art">
    <div style="display:flex;align-items:center;gap:10px">@if($logo)<img src="{{ $logo }}" alt="" style="height:38px">@else @include('partials.logo-mark') @endif<b style="font-size:16px">{{ $p['name'] }}</b></div>
    <svg class="ecg-bg" viewBox="0 0 1000 80" preserveAspectRatio="none" aria-hidden="true"><path d="M0 50h300l20-30 25 55 25-70 22 60 15-15h130l20-30 25 55 25-70 22 60 15-15h356"/></svg>
    <div><h1>Backoffice situs rumah sakit</h1><p>Kelola berita, tampilan situs, live chat, dan pantau janji temu dari SIMRS dalam satu tempat.</p></div>
  </div>
  <div class="login-form">
    <form method="post" action="{{ route('admin.login.post') }}">
      @csrf
      <h2>Masuk</h2>
      <p style="color:var(--muted);margin:0 0 24px">Gunakan akun petugas Anda.</p>
      <div class="fld"><label for="email">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" required autofocus></div>
      <div class="fld"><label for="password">Kata sandi</label><input id="password" name="password" type="password" autocomplete="current-password" required></div>
      <label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:14px"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
      @error('email')<div class="flash err" role="alert">{{ $message }}</div>@enderror
      <button class="btn pri" type="submit">Masuk ke backoffice</button>
    </form>
  </div>
</div>
</body>
</html>
