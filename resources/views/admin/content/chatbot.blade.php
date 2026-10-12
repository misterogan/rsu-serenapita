@extends('layouts.admin', ['current' => 'chatbot'])
@section('title', 'Chatbot')
@section('content')
<x-admin-head module="chatbot" title="Chatbot {{ $b['name'] }}" desc="Chatbot menjawab dengan aturan kata kunci, tanpa layanan AI luar. Jadwal dokter, cuti, poli, layanan, alamat, dan kontak diambil otomatis dari database." />
<div class="g2">
  <form class="card" method="post">@csrf<div class="card-b form">
    <div class="fld"><div class="tog-row"><div><b>Aktifkan chatbot</b><small>Jika dimatikan, panel chat hanya menampilkan live chat petugas.</small></div><label class="switch"><input type="checkbox" name="enabled" value="1" @checked($b['enabled'])><span></span></label></div></div>
    <div class="fld half"><label for="name">Nama asisten</label><input id="name" name="name" value="{{ old('name', $b['name']) }}"></div>
    <div class="fld"><label for="greeting">Sapaan pembuka</label><textarea id="greeting" name="greeting" rows="3">{{ old('greeting', $b['greeting']) }}</textarea></div>
    <div class="fld"><label for="quick">Pertanyaan cepat</label><textarea id="quick" name="quick" rows="3">{{ old('quick', implode("\n", $b['quick'])) }}</textarea><div class="hint">Satu pertanyaan per baris.</div></div>
    <div class="fld"><label for="faq">Informasi tambahan (FAQ)</label><textarea id="faq" name="faq" rows="8">{{ old('faq', $b['faq']) }}</textarea><div class="hint">Satu entri per baris dengan format <b>kata kunci, kata kunci: jawaban</b>. Contoh: <code>parkir, motor: Parkir gratis di halaman depan.</code> Entri FAQ didahulukan sebelum jawaban otomatis.</div></div>
    <div class="fld"><label for="emergency">Pesan darurat</label><textarea id="emergency" name="emergency" rows="2">{{ old('emergency', $b['emergency']) }}</textarea><div class="hint">Ditampilkan bila pesan mengandung kata seperti darurat, pingsan, kejang, nyeri dada, atau kecelakaan. Nomor IGD ditambahkan otomatis.</div></div>
    <div class="fld"><label for="fallback">Jawaban bila tidak paham <span style="font-weight:400;color:var(--muted)">(opsional)</span></label><textarea id="fallback" name="fallback" rows="3" placeholder="Kosongkan untuk memakai jawaban bawaan yang menyarankan topik dan live chat petugas">{{ old('fallback', $b['fallback'] ?? '') }}</textarea></div>
  </div><div class="save-bar"><button class="btn pri">Simpan chatbot</button></div></form>
  <div class="card"><div class="card-h"><div><h2>Uji coba</h2><p>Memakai pengaturan yang sudah disimpan. Coba: "dr maria besok praktik?", "poli gigi hari sabtu", "dokter yang cuti"</p></div></div><div class="card-b">
    <div class="bot-log" id="botLog"><div class="msg bot">{{ $b['greeting'] }}</div></div>
    <form class="bot-form" id="botTest" action="{{ route('admin.chatbot.test') }}" data-noload><input id="botIn" placeholder="Tulis pertanyaan uji" aria-label="Pertanyaan uji" autocomplete="off"><button class="btn pri">Kirim</button></form>
  </div></div>
</div>
@endsection
