@php($title = 'Edit Koperasi')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Koperasi #{{ $id }}</div>
  <form method="POST" action="{{ route('koperasi.update', $id) }}">
    @csrf @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Kode</label>
        <input type="text" name="kode_koperasi" value="KOP001" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>Nama</label>
        <input type="text" name="nama_koperasi" value="Koperasi Maju" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div style="grid-column:1/-1;">
        <label>Alamat</label>
        <textarea name="alamat" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">Jl. Mawar No.1</textarea>
      </div>
    </div>
    <div style="margin-top:12px;">
      <button class="btn-brand" type="submit">Simpan</button>
      <a class="btn-brand" href="{{ route('koperasi.index') }}">Batal</a>
    </div>
  </form>
  </div>
@endsection

