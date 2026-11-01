@extends('layouts.admin', ['current' => 'patients'])
@php use App\Services\Khanza\KhanzaPatients; @endphp
@section('title', 'Data pasien')
@section('content')
<x-admin-head module="patients" desc="Data pasien dan riwayat kunjungan dari SIMRS Khanza, hanya untuk dibaca. Setiap pembukaan data pasien tercatat di Log aktivitas." />

@if($unavailable)
  <div class="sync-note"><svg class="i" viewBox="0 0 24 24"><path d="M12 9v4M12 17h.01M10.3 3.9 2 18a2 2 0 0 0 1.7 3h16.6a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/></svg><div><b>Data pasien belum bisa ditampilkan.</b><span>{{ $unavailable }}</span></div></div>
@else
  <form class="card pt-search" id="tSearch" method="get" action="{{ route('admin.patients') }}" data-noload>
    <svg class="i" viewBox="0 0 24 24"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4"/></svg>
    <input type="search" name="q" value="{{ $term }}" placeholder="Cari No. RM, nama, NIK, No. BPJS, atau nomor HP…" aria-label="Cari pasien" autocomplete="off" autofocus minlength="3">
    <button class="btn pri">Cari</button>
  </form>
  @include('admin.management._errors')

  @if($term !== '')
    <div class="card">
      <div class="card-h"><div><h2>Hasil pencarian "{{ $term }}"</h2><p>{{ count($results) }} pasien{{ count($results) >= 50 ? ' (50 teratas; persempit kata pencarian)' : '' }}</p></div><a class="btn sm" href="{{ route('admin.patients') }}">Kunjungan hari ini</a></div>
      <div class="table-scroll"><table class="tbl" id="tResults">
        <thead><tr><th>Pasien</th><th>Lahir / umur</th><th>Alamat</th><th>Kontak & penjamin</th><th>Kunjungan</th></tr></thead>
        <tbody>
        @forelse($results as $r)
          <tr class="pt-link" data-href="{{ route('admin.patients.show', $r['no_rkm_medis']) }}">
            <td><a class="main" href="{{ route('admin.patients.show', $r['no_rkm_medis']) }}">{{ $r['nm_pasien'] }}</a><span class="sub">RM {{ $r['no_rkm_medis'] }} · {{ ($r['jk'] ?? '') === 'P' ? 'Perempuan' : 'Laki-laki' }}@if($r['no_ktp']) · NIK {{ $r['no_ktp'] }}@endif</span></td>
            <td>{{ $r['tgl_lahir'] ? Carbon\Carbon::parse($r['tgl_lahir'])->format('d/m/Y') : '—' }}<span class="sub">{{ KhanzaPatients::age($r['tgl_lahir']) }}</span></td>
            <td style="max-width:280px">{{ $r['alamat'] }}</td>
            <td>{{ $r['no_tlp'] ?: '—' }}<span class="sub">{{ $r['png_jawab'] }}@if($r['no_peserta']) · {{ $r['no_peserta'] }}@endif</span></td>
            <td>{{ $r['kunjungan'] }}×<span class="sub">{{ $r['terakhir'] ? 'terakhir '.Carbon\Carbon::parse($r['terakhir'])->translatedFormat('d M Y') : 'belum pernah' }}</span></td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty">{{ mb_strlen($term) < 3 ? 'Ketik minimal 3 huruf atau angka.' : 'Tidak ada pasien yang cocok. Coba No. RM lengkap, NIK, atau sebagian nama.' }}</div></td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div>
  @else
    <div class="card" id="tToday">
      <div class="card-h"><div><h2>Kunjungan {{ $date->isToday() ? 'hari ini' : $date->translatedFormat('l, d F Y') }}</h2><p>{{ count($today) }} pendaftaran di SIMRS Khanza. Klik nama untuk membuka data pasien.</p></div>
        <form class="stk-date" method="get" action="{{ route('admin.patients') }}" data-noload>
          <a class="ibtn" href="{{ route('admin.patients', ['tanggal' => $date->copy()->subDay()->toDateString()]) }}" aria-label="Hari sebelumnya"><svg class="i" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></a>
          <input type="date" name="tanggal" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" aria-label="Tanggal" onchange="this.form.submit()">
          @unless($date->isToday())<a class="ibtn" href="{{ route('admin.patients', ['tanggal' => $date->copy()->addDay()->toDateString()]) }}" aria-label="Hari berikutnya"><svg class="i" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></a>@endunless
        </form>
      </div>
      <div class="table-scroll"><table class="tbl">
        <thead><tr><th>Jam</th><th>Pasien</th><th>Poli / dokter</th><th>Jenis</th><th>Penjamin</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($today as $r)
          <tr class="pt-link" data-href="{{ route('admin.patients.show', $r['no_rkm_medis']) }}">
            <td>{{ substr((string) $r['jam_reg'], 0, 5) }}<span class="sub">No. {{ $r['no_reg'] }}</span></td>
            <td><a class="main" href="{{ route('admin.patients.show', $r['no_rkm_medis']) }}">{{ $r['nm_pasien'] }}</a><span class="sub">RM {{ $r['no_rkm_medis'] }} · {{ ($r['jk'] ?? '') === 'P' ? 'P' : 'L' }} · {{ KhanzaPatients::age($r['tgl_lahir']) }}</span></td>
            <td>{{ $r['nm_poli'] }}<span class="sub">{{ $r['nm_dokter'] }}</span></td>
            <td><span class="badge b-{{ $r['status_lanjut'] === 'Ranap' ? 'amber' : 'blue' }}">{{ $r['status_lanjut'] === 'Ranap' ? 'Rawat inap' : 'Rawat jalan' }}</span></td>
            <td>{{ $r['png_jawab'] }}</td>
            <td><span class="badge b-{{ $r['stts'] === 'Batal' ? 'red' : ($r['stts'] === 'Sudah' ? 'green' : 'grey') }}">{{ $r['stts'] }}</span></td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty">Belum ada pendaftaran pada tanggal ini.</div></td></tr>
        @endforelse
        </tbody>
      </table></div>
    </div>
  @endif
@endif
@include('admin.tours.patients')
@endsection
@push('scripts')
<script>
document.querySelectorAll(".pt-link").forEach(tr => tr.addEventListener("click", e => { if (!e.target.closest("a")) location.href = tr.dataset.href; }));
</script>
@endpush
