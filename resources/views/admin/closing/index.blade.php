@extends('layouts.admin', ['current' => 'closing'])
@use('App\Support\Fmt', 'F')
@section('title', 'Closing akuntansi')
@section('content')
@include('admin.closing._nav', ['actions' => $book->snap ? [['Unduh paket closing (Excel)', route('admin.closing.export', 'paket'), true]] : []])
@php $s = $book->settings; @endphp

@if($summary)
<div class="mg-kpis">
  <div class="mg-kpi"><small>Total aset</small><b class="sm">{{ F::rps($summary['aset']) }}</b><span>per cut off</span></div>
  <div class="mg-kpi"><small>Kewajiban</small><b class="sm">{{ F::rps($summary['kewajiban']) }}</b><span>per cut off</span></div>
  <div class="mg-kpi"><small>Ekuitas</small><b class="sm">{{ F::rps($summary['ekuitas']) }}</b><span>sebelum laba berjalan</span></div>
  <div class="mg-kpi"><small>Laba (rugi) berjalan</small><b class="sm">{{ F::rps($summary['laba']) }}</b><span>awal tahun s.d. cut off</span></div>
  <div class="mg-kpi" style="border-top-color:{{ abs($summary['selisih']) < 1 ? 'var(--green)' : 'var(--red)' }}"><small>Selisih neraca</small><b class="sm">{{ F::rps($summary['selisih']) }}</b><span>{{ abs($summary['selisih']) < 1 ? 'seimbang' : 'aset ≠ kewajiban + ekuitas + laba' }}</span></div>
  @if(abs($summary['belum']) >= 1)<div class="mg-kpi" style="border-top-color:var(--red)"><small>Belum dipetakan</small><b class="sm">{{ F::rps($summary['belum']) }}</b><span>saldo D−K rekening Khanza</span></div>@endif
</div>
@endif

<div class="g3">
  <div class="card mg-card">
    <div class="mg-h"><h2>Daftar periksa closing</h2></div>
    <ul class="cl-check">
      @foreach($checklist as $c)
        <li class="{{ $c['status'] }}"><i aria-hidden="true">{{ ['ok' => '✓', 'warn' => '!', 'bad' => '✕', 'info' => 'i'][$c['status']] }}</i>
          <div><b>{{ $c['label'] }}</b><span>{{ $c['detail'] }}</span></div>
          @if($c['link'])<a class="btn sm" href="{{ url('admin/closing/'.$c['link']) }}">Buka</a>@endif</li>
      @endforeach
    </ul>
  </div>

  <div>
    <div class="card mg-card">
      <div class="mg-h"><h2>Pengaturan</h2></div>
      <form method="post" action="{{ route('admin.closing.settings') }}" class="form">
        @csrf
        <div class="fld half"><label for="clYs">Awal tahun buku</label><input id="clYs" type="date" name="year_start" value="{{ old('year_start', $s['year_start']) }}" @disabled(!$canEdit || $book->locked())></div>
        <div class="fld half"><label for="clCut">Tanggal cut off</label><input id="clCut" type="date" name="cutoff" value="{{ old('cutoff', $s['cutoff']) }}" @disabled(!$canEdit || $book->locked())>
          <div class="hint">Hari terakhir Khanza dipakai penuh, sehari sebelum SIMRS baru go-live. Saldo awal SIMRS baru per hari berikutnya.</div></div>
        <div class="fld"><label for="clOp">Sumber saldo awal tahun</label>
          <select id="clOp" name="opening" @disabled(!$canEdit || $book->locked())>@foreach($openings as $k => $label)<option value="{{ $k }}" @selected(old('opening', $s['opening']) === $k)>{{ $label }}</option>@endforeach</select>
          <div class="hint">Pakai "akumulasi jurnal" bila saldo awal tahun belum pernah diisi di Khanza. Laba rugi tahun-tahun sebelumnya lalu muncul sebagai satu rekening untuk dipetakan ke Saldo Laba Ditahan.</div></div>
        @if($canEdit && !$book->locked())<div class="fld"><button class="btn">Simpan pengaturan</button></div>@endif
      </form>
    </div>

    <div class="card mg-card">
      <div class="mg-h"><h2>Data Khanza</h2></div>
      @if($book->snap)
        <p class="mg-note">Terakhir diambil {{ $book->snap->created_at->translatedFormat('d F Y H:i') }} oleh {{ $book->snap->user_name }}, untuk periode
          {{ $book->snap->year_start->translatedFormat('d M Y') }} – {{ $book->snap->cutoff->translatedFormat('d M Y') }}. Data ini disimpan di website, jadi closing tetap bisa dikerjakan walaupun server Khanza sudah dimatikan.</p>
      @else
        <p class="mg-note">Data buku besar, piutang, hutang, dan persediaan dibaca dari Khanza lalu disalin ke website. Proses bisa memakan waktu beberapa menit.</p>
      @endif
      @if(!$configured)
        <div class="flash err" style="margin:0">Koneksi Khanza belum diatur di server (lihat database/khanza/README.txt).</div>
      @elseif($canEdit && !$book->locked())
        <form method="post" action="{{ route('admin.closing.snapshot') }}" @if($book->snap) data-confirm="Data Khanza akan diambil ulang. Pemetaan akun dan jurnal penyesuaian tetap tersimpan." @endif>@csrf
          <button class="btn pri">{{ $book->snap ? 'Ambil ulang data dari Khanza' : 'Ambil data dari Khanza' }}</button></form>
      @endif
      @if($book->snap?->errors)
        <details class="flash err mg-errs" style="margin:12px 0 0"><summary>{{ count($book->snap->errors) }} bagian tidak bisa dibaca dari Khanza (untuk tim IT)</summary>
          <ul>@foreach($book->snap->errors as $k => $m)<li><b>{{ $k }}</b>: {{ $m }}</li>@endforeach</ul></details>
      @endif
    </div>

    @if($book->snap)
    <div class="card mg-card">
      <div class="mg-h"><h2>Saldo awal SIMRS baru</h2></div>
      <p class="mg-note">Saldo per akun COA per {{ \Illuminate\Support\Carbon::parse($s['cutoff'])->addDay()->translatedFormat('d F Y') }}, setelah jurnal penyesuaian.</p>
      <form method="get" action="{{ route('admin.closing.export', 'saldo-awal') }}" class="cl-form" data-noload>
        <label>Saldo laba rugi<select name="mode"><option value="tutup">Ditutup ke {{ config('closing.current_earnings') }} Laba Tahun Berjalan</option><option value="rinci">Dibawa per akun pendapatan/beban</option></select></label>
        <button class="btn">Unduh (Excel)</button>
      </form>
    </div>
    @endif

    @if($canEdit)
    <div class="card mg-card">
      <div class="mg-h"><h2>Kunci closing</h2></div>
      @if($book->locked())
        <p class="mg-note">Dikunci {{ \Illuminate\Support\Carbon::parse($s['locked_at'])->translatedFormat('d M Y H:i') }} oleh {{ $s['locked_by'] }}. Pemetaan, penyesuaian, dan pengambilan data tidak bisa diubah.</p>
        <form method="post" action="{{ route('admin.closing.lock') }}" data-confirm="Buka kunci closing? Angka bisa berubah lagi.">@csrf<input type="hidden" name="action" value="unlock"><button class="btn">Buka kunci</button></form>
      @else
        <p class="mg-note">Kunci setelah saldo awal diunggah ke SIMRS baru, agar angka di website sama dengan yang diunggah.</p>
        <form method="post" action="{{ route('admin.closing.lock') }}" data-confirm="Kunci closing? Pemetaan dan jurnal penyesuaian tidak bisa diubah sampai kunci dibuka.">@csrf<input type="hidden" name="action" value="lock"><button class="btn">Kunci closing</button></form>
      @endif
    </div>
    @endif
  </div>
</div>

<div class="card mg-card">
  <div class="mg-h"><h2>Urutan kerja</h2></div>
  <ol class="cl-steps">
    <li>Periksa tanggal cut off dan awal tahun buku, lalu <b>ambil data dari Khanza</b>.</li>
    <li><b>COA baru</b>: pastikan daftar akun sama dengan template SIMRS baru (bisa diunggah ulang).</li>
    <li><b>Pemetaan akun</b>: hubungkan setiap rekening Khanza yang bersaldo ke satu akun COA. Gunakan saran otomatis lalu periksa.</li>
    <li><b>Neraca saldo</b>: pastikan total debet = kredit. Selisih biasanya dari saldo awal tahun atau jurnal Khanza yang tidak seimbang.</li>
    <li><b>Rekonsiliasi</b>: cocokkan saldo piutang, hutang, dan persediaan dengan rinciannya. Buat <b>jurnal penyesuaian</b> untuk selisih yang sudah dijelaskan.</li>
    <li>Unduh <b>saldo awal SIMRS baru</b> dan rinciannya, lalu unggah ke SIMRS baru.</li>
    <li><b>Ranap lanjutan</b>: setelah semua pasien yang dirawat saat cut off pulang, ambil ulang data, lalu catat ringkasan jurnalnya per bulan di SIMRS baru. Setelah itu <b>kunci closing</b>.</li>
    <li>Untuk laporan tahunan: gabungkan laba rugi per bulan dari sini (Januari s.d. cut off) dengan laporan SIMRS baru (setelah cut off s.d. Desember).</li>
  </ol>
</div>
@endsection
