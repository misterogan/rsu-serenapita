@extends('layouts.admin', ['current' => 'profile'])
@section('title', 'Profil & kontak')
@php $logo = $p['logo'] ? asset('storage/'.$p['logo']) : null; $logoDark = $p['logo_dark'] ? asset('storage/'.$p['logo_dark']) : null; $share = \App\Support\SiteSettings::shareImageUrl(); $rows = old('phones', $phones->map(fn ($x) => $x->only(['label','number','kind','show_top','show_igd','show_contact','show_footer']))->all()); @endphp
@section('content')
<form method="post" enctype="multipart/form-data">
@csrf
<x-admin-head module="profile" desc="Logo, alamat, dan nomor telepon di sini dipakai di seluruh situs: bar atas, header, kotak IGD, halaman Kontak, footer, dan jawaban chatbot.">
  <button class="btn pri">Simpan profil</button>
</x-admin-head>
<div style="display:flex;flex-direction:column;gap:20px">
  <div class="card"><div class="card-h"><div><h2>Identitas & logo</h2><p>PNG, JPG, WebP, atau SVG maksimal 1 MB.</p></div></div><div class="card-b">
    <div class="form" style="margin-bottom:16px">
      <div class="fld half"><label for="name">Nama rumah sakit</label><input id="name" name="name" value="{{ old('name', $p['name']) }}"></div>
      <div class="fld half"><label for="short">Keterangan di bawah nama</label><input id="short" name="short" value="{{ old('short', $p['short']) }}"></div>
    </div>
    <div class="logo-slots">
      @foreach([['logo', 'Logo utama', 'Untuk latar terang: header situs.', $logo, 'light'], ['logo_dark', 'Logo untuk latar gelap', 'Footer, sidebar backoffice, dan halaman masuk. Jika kosong memakai logo utama.', $logoDark, 'dark'], ['share_image', 'Gambar saat link dibagikan', 'Tampil di WhatsApp, Facebook, dan media sosial saat link situs dibagikan. Ukuran ideal 1200 × 630 px, JPG atau PNG, maksimal 600 KB.', $share, 'light']] as [$k, $t, $d, $url, $tone])
      <div class="logo-slot"><div class="logo-prev {{ $tone }}" id="prev_{{ $k }}">@if($url)<img src="{{ $url }}" alt="">@else @include('partials.logo-mark', ['size' => 54]) @endif</div>
        <div><b>{{ $t }}</b><small>{{ $d }}</small><input type="file" name="{{ $k }}" accept="{{ $k === 'share_image' ? 'image/png,image/jpeg' : 'image/png,image/jpeg,image/webp,image/svg+xml' }}" data-preview="#prev_{{ $k }}" aria-label="Unggah {{ $t }}">
          @if(!empty($p[$k]))<label style="display:block;font-size:12px;margin-top:6px"><input type="checkbox" name="{{ $k }}_remove" value="1"> {{ $k === 'share_image' ? 'Pakai gambar bawaan' : 'Pakai logo bawaan' }}</label>@endif</div></div>
      @endforeach
    </div>
  </div></div>

  <div class="card"><div class="card-h"><div><h2>Alamat</h2><p>{{ \App\Support\SiteSettings::address($p) }}</p></div></div><div class="card-b form">
    <div class="fld"><label for="street">Jalan dan nomor</label><input id="street" name="street" value="{{ old('street', $p['street']) }}"></div>
    <div class="fld half"><label for="district">Kecamatan</label><input id="district" name="district" value="{{ old('district', $p['district']) }}"></div>
    <div class="fld half"><label for="city">Kabupaten / kota</label><input id="city" name="city" value="{{ old('city', $p['city']) }}"></div>
    <div class="fld half"><label for="province">Provinsi</label><input id="province" name="province" value="{{ old('province', $p['province']) }}"></div>
    <div class="fld half"><label for="zip">Kode pos</label><input id="zip" name="zip" value="{{ old('zip', $p['zip']) }}"></div>
    <div class="fld"><label for="maps">Tautan Google Maps</label><input id="maps" name="maps" value="{{ old('maps', $p['maps']) }}"><div class="hint">Dipakai tombol Buka di Google Maps di halaman Kontak.</div></div>
  </div></div>

  <div class="card"><div class="card-h"><div><h2>Nomor telepon</h2><p>Centang di mana setiap nomor ditampilkan. Nomor "Kotak IGD" tampil besar di beranda.</p></div>
    <button type="button" class="btn sm" data-add-row="#phoneRows" data-tpl="#phoneTpl">+ Tambah nomor</button></div>
    <div class="table-scroll"><table class="tbl ph-tbl"><thead><tr><th>Keterangan</th><th>Nomor</th><th>Jenis</th><th class="c">Bar atas</th><th class="c">Kotak IGD</th><th class="c">Kontak</th><th class="c">Footer</th><th></th></tr></thead>
    <tbody id="phoneRows">
      @foreach($rows as $i => $ph)
      <tr><td><input type="text" name="phones[{{ $i }}][label]" value="{{ $ph['label'] ?? '' }}" aria-label="Keterangan"></td><td><input type="text" name="phones[{{ $i }}][number]" value="{{ $ph['number'] ?? '' }}" inputmode="tel" aria-label="Nomor"></td>
        <td><select name="phones[{{ $i }}][kind]" aria-label="Jenis"><option value="tel" @selected(($ph['kind'] ?? '') === 'tel')>Telepon</option><option value="wa" @selected(($ph['kind'] ?? '') === 'wa')>WhatsApp</option></select></td>
        @foreach(['show_top', 'show_igd', 'show_contact', 'show_footer'] as $f)<td class="c"><input type="checkbox" name="phones[{{ $i }}][{{ $f }}]" value="1" @checked(!empty($ph[$f])) aria-label="{{ $f }}"></td>@endforeach
        <td><button type="button" class="ibtn del" data-del-row aria-label="Hapus nomor">×</button></td></tr>
      @endforeach
    </tbody></table></div>
    <template id="phoneTpl"><tr><td><input type="text" name="phones[__i__][label]" aria-label="Keterangan"></td><td><input type="text" name="phones[__i__][number]" inputmode="tel" aria-label="Nomor"></td><td><select name="phones[__i__][kind]"><option value="tel">Telepon</option><option value="wa">WhatsApp</option></select></td><td class="c"><input type="checkbox" name="phones[__i__][show_top]" value="1"></td><td class="c"><input type="checkbox" name="phones[__i__][show_igd]" value="1"></td><td class="c"><input type="checkbox" name="phones[__i__][show_contact]" value="1" checked></td><td class="c"><input type="checkbox" name="phones[__i__][show_footer]" value="1"></td><td><button type="button" class="ibtn del" data-del-row aria-label="Hapus nomor">×</button></td></tr></template>
    <div class="card-b form" style="border-top:1px solid var(--line)"><div class="fld"><label for="email">Email</label><input id="email" name="email" value="{{ old('email', $p['email']) }}"></div></div>
  </div>

  <div class="card"><div class="card-h"><h2>Jam dan teks lain</h2></div><div class="card-b form">
    <div class="fld"><label for="counter_hours">Jam loket pendaftaran</label><input id="counter_hours" name="counter_hours" value="{{ old('counter_hours', $p['counter_hours']) }}"></div>
    <div class="fld"><label for="visiting_hours">Jam besuk</label><input id="visiting_hours" name="visiting_hours" value="{{ old('visiting_hours', $p['visiting_hours']) }}"></div>
    <div class="fld"><label for="footer">Teks pendek di footer</label><textarea id="footer" name="footer" rows="2">{{ old('footer', $p['footer']) }}</textarea></div>
  </div></div>
  @php $so = \App\Support\SiteSettings::get('social'); $seo = \App\Support\SiteSettings::get('seo'); @endphp
  <div class="card"><div class="card-h"><div><h2>Media sosial resmi</h2><p>Tampil sebagai ikon di footer, dan memberi tahu Google bahwa akun-akun ini milik rumah sakit yang sama.</p></div></div><div class="card-b form">
    @foreach(['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'] as $k => $lb)
    <div class="fld half"><label for="so_{{ $k }}">{{ $lb }}</label><input id="so_{{ $k }}" name="social[{{ $k }}]" type="url" placeholder="https://" value="{{ old('social.'.$k, $so[$k] ?? '') }}">@error('social.'.$k)<div class="err">{{ $message }}</div>@enderror</div>
    @endforeach
  </div></div>
  <div class="card"><div class="card-h"><div><h2>Pencarian Google (SEO)</h2><p>Mengatur cara website tampil di hasil pencarian Google.</p></div></div><div class="card-b form">
    <div class="fld"><label for="home_title">Judul beranda di Google</label><input id="home_title" name="seo[home_title]" maxlength="70" value="{{ old('seo.home_title', $seo['home_title']) }}"><div class="hint">Maks. sekitar 60 karakter agar tidak terpotong. Sebutkan nama rumah sakit dan kota.</div></div>
    <div class="fld"><label for="home_description">Deskripsi beranda di Google</label><textarea id="home_description" name="seo[home_description]" rows="3" maxlength="200">{{ old('seo.home_description', $seo['home_description']) }}</textarea><div class="hint">Sekitar 150 karakter. Ringkas layanan utama dan lokasi.</div></div>
    <div class="fld half"><label for="alt_names">Nama lain rumah sakit</label><textarea id="alt_names" name="seo[alt_names]" rows="5">{{ old('seo.alt_names', $seo['alt_names']) }}</textarea><div class="hint">Satu nama per baris. Membantu Google mengenali berbagai cara orang menyebut rumah sakit.</div></div>
    <div class="fld half"><label for="city">Kota untuk judul halaman</label><input id="city" name="seo[city]" value="{{ old('seo.city', $seo['city']) }}"><div class="hint">Dipakai di judul halaman poli dan dokter, misalnya "Poli Anak di Sidikalang".</div></div>
  </div><div class="save-bar"><button class="btn pri">Simpan profil</button></div></div>
</div>
</form>
@endsection
