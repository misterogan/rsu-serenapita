@extends('layouts.admin', ['current' => 'stock'])
@php use App\Models\StockEntry; use App\Support\StockLedger; @endphp
@section('title', 'Input stok harian')
@section('content')
@php
  $q = fn (array $over = []) => route('admin.stock', array_filter(array_merge(['unit' => $loc, 'date' => $date->toDateString(), 'opname' => $opname ?: null], $over), fn ($v) => $v !== null));
  $old = old('rows', []);
  $groups = $medicines->pluck('group')->unique()->values();
  $lastBy = $entries->sortByDesc('updated_at')->first();
@endphp
<x-admin-head module="stock" desc="Pengganti lembar Excel persediaan. Pilih unit dan tanggal, lalu ketik jumlah obat yang masuk dan keluar hari itu. Sisa stok dihitung otomatis.">
  @if(auth()->user()->canModule('stock-report'))<a class="btn" id="tRekap" href="{{ route('admin.stock.report', ['unit' => $loc]) }}">Rekap persediaan</a>@endif
</x-admin-head>

<div class="card stk-bar">
  <div class="seg" id="tUnit" role="group" aria-label="Unit">
    @foreach(StockEntry::LOCATIONS as $k => $n)<a href="{{ $q(['unit' => $k]) }}" class="{{ $loc === $k ? 'on' : '' }}" @if($loc === $k) aria-current="true" @endif>{{ $n }}</a>@endforeach
  </div>
  <form class="stk-date" method="get" action="{{ route('admin.stock') }}" data-noload>
    <input type="hidden" name="unit" value="{{ $loc }}">@if($opname)<input type="hidden" name="opname" value="1">@endif
    <a class="ibtn" href="{{ $q(['date' => $date->copy()->subDay()->toDateString()]) }}" title="Hari sebelumnya" aria-label="Hari sebelumnya"><svg class="i" viewBox="0 0 24 24"><path d="M15 5l-7 7 7 7"/></svg></a>
    <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ today()->toDateString() }}" aria-label="Tanggal">
    @if($date->lt(today()))<a class="ibtn" href="{{ $q(['date' => $date->copy()->addDay()->toDateString()]) }}" title="Hari berikutnya" aria-label="Hari berikutnya"><svg class="i" viewBox="0 0 24 24"><path d="M9 5l7 7-7 7"/></svg></a>
    <a class="btn sm" href="{{ $q(['date' => today()->toDateString()]) }}">Hari ini</a>@endif
    <span class="stk-day">{{ $date->translatedFormat('l, d F Y') }}</span>
  </form>
  <div class="seg" id="tMode" role="group" aria-label="Jenis isian">
    <a href="{{ $q(['opname' => null]) }}" class="{{ $opname ? '' : 'on' }}">Masuk & keluar</a>
    <a href="{{ $q(['opname' => 1]) }}" class="{{ $opname ? 'on' : '' }}">Stock opname</a>
  </div>
</div>

<div class="stk-note" id="tNote">
  @if($opname)
    <b>Mode stock opname.</b> Hitung fisik obat di rak <b>sebelum ada transaksi hari ini</b>, lalu ketik jumlahnya di kolom <i>Hasil hitung</i>. Hasil hitung menjadi stok awal hari ini, dan selisihnya dengan stok sistem tercatat di rekap. Masuk dan keluar hari ini tetap dihitung. Kosongkan bila obat tidak dihitung.
  @else
    <b>{{ $entries->count() }} obat</b> sudah diisi untuk tanggal ini{!! $lastBy ? ', terakhir oleh <b>'.e($lastBy->user?->name ?? 'petugas').'</b> pukul '.$lastBy->updated_at->format('H.i') : '' !!}.
    Cukup isi obat yang ada transaksinya; kolom kosong berarti 0. Tekan <kbd>Enter</kbd> untuk turun ke baris berikutnya.
    @if($loc === 'sentral' && $fromGudang) Angka biru <span class="fill-demo">dari gudang</span> bisa diklik untuk mengisi Masuk sesuai barang yang dikeluarkan gudang hari ini.@endif
  @endif
</div>

<form method="post" action="{{ route('admin.stock.save') }}" id="stkForm">
  @csrf
  <input type="hidden" name="location" value="{{ $loc }}">
  <input type="hidden" name="date" value="{{ $date->toDateString() }}">
  @if($opname)<input type="hidden" name="opname" value="1">@endif

  <div class="card">
    <div class="list-tools stk-tools">
      <input type="search" id="stkQ" placeholder="Cari nama obat…  ( / )" aria-label="Cari nama obat" autocomplete="off">
      <select id="stkG" aria-label="Golongan"><option value="">Semua golongan</option>@foreach($groups as $g)<option>{{ $g }}</option>@endforeach</select>
      <label class="chk" id="tFilled"><input type="checkbox" id="stkFilled"> Hanya yang sudah diisi</label>
      @unless($opname)<label class="chk"><input type="checkbox" id="stkLow"> Hanya stok menipis</label>@endunless
      <span class="count" id="stkCount">{{ $medicines->count() }} obat</span>
    </div>
    <div class="table-scroll stk-scroll">
      <table class="tbl stk" id="stkTbl">
        <thead><tr>
          <th>Nama obat</th>
          @if($opname)
            <th class="n">Stok sistem</th><th class="n">Hasil hitung</th><th class="n">Selisih</th><th>Tanggal ED</th>
          @else
            <th class="n">Stok awal</th><th class="n">Masuk</th><th class="n">Keluar</th><th class="n">Sisa</th>
          @endif
          <th>Catatan</th>
        </tr></thead>
        <tbody>
        @php $curGroup = null; @endphp
        @foreach($medicines as $m)
          @php
            $e = $entries[$m->id] ?? null;
            $o = $old[$m->id] ?? null;
            $start = $summary[$m->id]['start'] ?? 0;
            $in = $o['in'] ?? $e?->qty_in;
            $out = $o['out'] ?? $e?->qty_out;
            $counted = $o['counted'] ?? $e?->counted;
            $note = $o['note'] ?? $e?->note;
            $rop = StockLedger::rop($avg[$m->id] ?? 0);
            $ed = $m->expiryState();
            $n = fn ($f) => $o !== null ? 'name=rows['.$m->id.']['.$f.']' : '';
          @endphp
          @if($m->group !== $curGroup)
            @php $curGroup = $m->group; @endphp
            <tr class="stk-grp" data-group="{{ $m->group }}"><td colspan="6">{{ $m->group }}</td></tr>
          @endif
          <tr class="stk-row{{ $o !== null ? ' dirty' : '' }}" data-id="{{ $m->id }}" data-group="{{ $m->group }}" data-name="{{ mb_strtolower($m->name) }}" data-start="{{ $start }}" data-rop="{{ $rop }}" data-filled="{{ $e ? 1 : 0 }}">
            <td class="nm"><span class="main">{{ $m->name }}</span>
              <span class="sub">{{ $m->unit }}@if($ed) · <span class="ed-{{ $ed }}">{{ $ed === 'expired' ? 'Kedaluwarsa' : 'ED' }} {{ $m->expired_at->translatedFormat('M Y') }}</span>@endif
              @if($e?->counted !== null && !$opname) · <span title="Stok awal hari ini mengikuti hasil stock opname">Stok awal dari SO</span>@endif</span></td>
            @if($opname)
              <td class="n"><b class="sys">{{ $start }}</b>
                <input type="hidden" data-f="in" value="{{ $in }}" {{ $n('in') }}><input type="hidden" data-f="out" value="{{ $out }}" {{ $n('out') }}></td>
              <td class="n"><input class="q" data-f="counted" inputmode="numeric" autocomplete="off" value="{{ $counted }}" aria-label="Hasil hitung {{ $m->name }}" {{ $n('counted') }}></td>
              <td class="n"><span class="diff"></span></td>
              <td><input type="date" class="dt" data-f="expired_at" value="{{ $o['expired_at'] ?? $m->expired_at?->toDateString() }}" aria-label="Tanggal ED {{ $m->name }}" {{ $n('expired_at') }}></td>
            @else
              <td class="n">{{ $counted ?? $start }}</td>
              <td class="n"><input class="q" data-f="in" inputmode="numeric" autocomplete="off" value="{{ $in ?: '' }}" aria-label="Masuk {{ $m->name }}" {{ $n('in') }}>
                @if(isset($fromGudang[$m->id]) && (int) $in !== (int) $fromGudang[$m->id])<button type="button" class="fill" data-v="{{ $fromGudang[$m->id] }}" title="Isi sesuai barang keluar dari gudang">dari gudang {{ $fromGudang[$m->id] }}</button>@endif</td>
              <td class="n"><input class="q" data-f="out" inputmode="numeric" autocomplete="off" value="{{ $out ?: '' }}" aria-label="Keluar {{ $m->name }}" {{ $n('out') }}></td>
              <td class="n"><b class="end"></b>@if($counted !== null)<input type="hidden" data-f="counted" value="{{ $counted }}" {{ $n('counted') }}>@endif</td>
            @endif
            <td><input class="nt" data-f="note" maxlength="150" autocomplete="off" value="{{ $note }}" placeholder="{{ $opname ? '' : ($loc === 'gudang' ? 'mis. dari PBF / ke farmasi' : 'mis. ranap, IGD') }}" aria-label="Catatan {{ $m->name }}" {{ $n('note') }}></td>
          </tr>
        @endforeach
        </tbody>
      </table>
      <div class="empty" id="stkEmpty" hidden>Tidak ada obat yang cocok. Obat baru bisa ditambahkan di menu <a href="{{ url('admin/medicines') }}">Data obat</a>.</div>
    </div>
  </div>

  <div class="stk-save" id="stkSave">
    <span id="stkDirty">Belum ada perubahan</span>
    <button type="button" class="btn" id="stkReset" disabled>Batalkan</button>
    <button class="btn pri" id="stkSubmit" disabled>Simpan</button>
  </div>
</form>
@endsection
@include('admin.tours.stock')
@push('scripts')<script src="{{ asset('js/stock.js') }}?v=2"></script>@endpush
