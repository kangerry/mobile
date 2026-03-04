@php($title = 'Tambah Merchant')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Form Merchant</div>
  <form method="POST" action="{{ route('merchant.store') }}">@csrf
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
      <div><label>Nama Toko</label><input name="nama_toko" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;"><label>Deskripsi Toko</label><textarea name="deskripsi" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></textarea></div>
      <div><label>Pemilik</label><input name="nama_pemilik" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Email</label><input type="email" name="email" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Telepon</label><input name="telepon" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;"><label>Alamat</label><input name="alamat" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Kota</label><input name="kota" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Provinsi</label><input name="provinsi" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>No. NIB (Opsional)</label><input name="nib" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>No. PIRT (Opsional)</label><input name="pirt" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Latitude</label><input name="latitude" type="number" step="any" value="0" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Longitude</label><input name="longitude" type="number" step="any" value="0" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Anggota ID (Opsional)</label><input name="anggota_id" type="number" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px" placeholder="ID anggota pengaju"></div>
      <div>
        <label>Status</label>
        <select name="status" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          <option value="aktif">Aktif</option>
          <option value="pending" selected>Pending</option>
          <option value="nonaktif">Nonaktif</option>
        </select>
      </div>
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection
