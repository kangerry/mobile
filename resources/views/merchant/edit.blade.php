@php($title = 'Edit Merchant')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Merchant</div>
  <form method="POST" action="{{ route('merchant.update', $row->id ?? 0) }}">@csrf @method('PUT')
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
      <div><label>Nama Toko</label><input name="nama_toko" value="{{ $row->nama_toko ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;"><label>Deskripsi Toko</label><textarea name="deskripsi" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">{{ $row->deskripsi ?? '' }}</textarea></div>
      <div><label>Pemilik</label><input name="nama_pemilik" value="{{ $row->nama_pemilik ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Email</label><input type="email" name="email" value="{{ $row->email ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Telepon</label><input name="telepon" value="{{ $row->telepon ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;"><label>Alamat</label><input name="alamat" value="{{ $row->alamat ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Kota</label><input name="kota" value="{{ $row->kota ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Provinsi</label><input name="provinsi" value="{{ $row->provinsi ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>No. NIB (Opsional)</label><input name="nib" value="{{ $row->nib ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>No. PIRT (Opsional)</label><input name="pirt" value="{{ $row->pirt ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Latitude</label><input name="latitude" type="number" step="any" value="{{ $row->latitude ?? 0 }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Longitude</label><input name="longitude" type="number" step="any" value="{{ $row->longitude ?? 0 }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Anggota ID (Opsional)</label><input name="anggota_id" type="number" value="{{ $row->anggota_id ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div>
        <label>Status</label>
        <select name="status" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          <option value="aktif" @selected(($row->status ?? '') === 'aktif')>Aktif</option>
          <option value="pending" @selected(($row->status ?? '') === 'pending')>Pending</option>
          <option value="nonaktif" @selected(($row->status ?? '') === 'nonaktif')>Nonaktif</option>
        </select>
      </div>
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection
