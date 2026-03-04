@php($title = 'Tambah Koperasi')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Form Koperasi</div>
  <form method="POST" action="{{ route('koperasi.store') }}">
    @csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Kode</label>
        <input type="text" name="kode_koperasi" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>Nama</label>
        <input type="text" name="nama_koperasi" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div style="grid-column:1/-1;">
        <label>Alamat</label>
        <textarea name="alamat" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></textarea>
      </div>
    </div>
    <div style="margin-top:12px;">
      <button class="btn-brand" type="submit">Simpan</button>
      <a class="btn-brand" href="{{ route('koperasi.index') }}">Batal</a>
    </div>
  </form>
  </div>
@endsection

