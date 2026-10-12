@extends('layouts.admin', ['current' => 'info'])
@section('title', 'Kotak info & jam')
@section('content')
<x-admin-head module="info" title="Kotak info & jam pelayanan" desc="Tiga kotak berwarna di bawah slider beranda." />
<form method="post">
  @csrf
  <div class="g2" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
  @foreach($boxes as $i => $b)
    <div class="card" style="border-top:4px solid {{ ['#C8332E', '#35A3D8', '#1C3B70'][$i] }}"><div class="card-h"><h2>Kotak {{ ['merah', 'biru muda', 'biru tua'][$i] }}</h2></div><div class="card-b form">
      <div class="fld"><label for="b{{ $i }}t">Judul</label><input id="b{{ $i }}t" name="boxes[{{ $i }}][title]" value="{{ old("boxes.$i.title", $b['title']) }}"></div>
      @if($i < 2)
        <div class="fld"><label for="b{{ $i }}x">Teks</label><textarea id="b{{ $i }}x" name="boxes[{{ $i }}][text]" rows="3">{{ old("boxes.$i.text", $b['text']) }}</textarea></div>
        <div class="fld half"><label for="b{{ $i }}b">Teks tombol</label><input id="b{{ $i }}b" name="boxes[{{ $i }}][button]" value="{{ old("boxes.$i.button", $b['button']) }}"></div>
        <div class="fld half"><label for="b{{ $i }}l">Tujuan</label><select id="b{{ $i }}l" name="boxes[{{ $i }}][link]">@foreach($links as $k => $l)<option value="{{ $k }}" @selected($b['link'] === $k)>{{ $l }}</option>@endforeach</select></div>
        @if($i === 0)<div class="fld"><div class="tog-row"><div><b>Tampilkan nomor IGD besar</b><small>Nomor yang dicentang "Kotak IGD" di Profil & kontak</small></div><label class="switch"><input type="checkbox" name="show_phone" value="1" @checked($b['show_phone'])><span></span></label></div></div>@endif
      @else
        <input type="hidden" name="boxes[2][text]" value=""><input type="hidden" name="boxes[2][button]" value=""><input type="hidden" name="boxes[2][link]" value="">
        <div class="fld"><label>Baris jam pelayanan</label>
          <table class="tbl" style="min-width:0"><tbody id="hoursRows">
          @foreach($hours ?: [['label' => '', 'value' => '']] as $k => $h)
            <tr><td style="padding:4px"><input name="hours[{{ $k }}][label]" value="{{ $h['label'] }}" aria-label="Keterangan" style="width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:4px;background:var(--bg)"></td><td style="padding:4px;width:120px"><input name="hours[{{ $k }}][value]" value="{{ $h['value'] }}" aria-label="Jam" style="width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:4px;background:var(--bg)"></td><td style="padding:4px;width:1%"><button type="button" class="ibtn del" data-del-row aria-label="Hapus baris">×</button></td></tr>
          @endforeach
          </tbody></table>
          <template id="hourTpl"><tr><td style="padding:4px"><input name="hours[__i__][label]" aria-label="Keterangan" style="width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:4px;background:var(--bg)"></td><td style="padding:4px;width:120px"><input name="hours[__i__][value]" aria-label="Jam" style="width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:4px;background:var(--bg)"></td><td style="padding:4px"><button type="button" class="ibtn del" data-del-row aria-label="Hapus baris">×</button></td></tr></template>
          <button type="button" class="btn sm" data-add-row="#hoursRows" data-tpl="#hourTpl" style="margin-top:8px">+ Tambah baris</button>
        </div>
      @endif
    </div></div>
  @endforeach
  </div>
  <div style="display:flex;justify-content:flex-end;margin-top:16px"><button class="btn pri">Simpan kotak info</button></div>
</form>
@endsection
