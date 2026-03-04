@php($title = 'Edit Pesanan Ojek')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Pesanan Ojek</div>
  <form method="POST" action="{{ route('pesanan-ojek.update', $row->id ?? 0) }}">@csrf @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Koperasi</label>
        @if(auth()->check() && auth()->user()->hasRole('superadmin'))
          <select name="koperasi_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
            @foreach(($koperasis ?? []) as $k)
            <option value="{{ $k->id }}" @selected(($row->koperasi_id ?? null) == $k->id)>{{ $k->nama_koperasi }}</option>
            @endforeach
          </select>
        @else
          <input type="hidden" name="koperasi_id" value="{{ auth()->user()->koperasi_id }}">
          <div class="form-static">{{ optional(($koperasis ?? collect())->first())->nama_koperasi }}</div>
        @endif
      </div>
      <div><label>Nomor Pesanan</label><input name="nomor_pesanan" value="{{ $row->nomor_pesanan ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div>
        <label>Anggota</label>
        <select name="anggota_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          @foreach(($anggota ?? []) as $a)
          <option value="{{ $a->id }}" @selected(($row->anggota_id ?? null) == $a->id)>{{ $a->nama_anggota }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Driver</label>
        <select name="driver_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          <option value="">-</option>
          @foreach(($drivers ?? []) as $d)
          <option value="{{ $d->id }}" @selected(($row->driver_id ?? null) == $d->id)>{{ $d->nama_driver }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection
