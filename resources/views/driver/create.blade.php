@php($title = 'Tambah Driver')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Form Driver</div>
  <form method="POST" action="{{ route('driver.store') }}">@csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Koperasi</label>
        @if(auth()->check() && auth()->user()->hasRole('superadmin'))
          <select name="koperasi_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
            @foreach(($koperasis ?? []) as $k)
            <option value="{{ $k->id }}">{{ $k->nama_koperasi }}</option>
            @endforeach
          </select>
        @else
          <input type="hidden" name="koperasi_id" value="{{ auth()->user()->koperasi_id }}">
          <div class="form-static">{{ optional(($koperasis ?? collect())->first())->nama_koperasi }}</div>
        @endif
      </div>
      <div><label>Nama</label><input name="nama_driver" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Email</label><input type="email" name="email" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Telepon</label><input name="telepon" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Jenis Kendaraan</label><input name="jenis_kendaraan" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Plat Nomor</label><input name="plat_nomor" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Nomor SIM</label><input name="nomor_sim" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection
