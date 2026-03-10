@php($title = 'Edit Kategori Produk')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Kategori Produk</div>
  <form method="POST" action="{{ route('kategori-produk.update', $row->id ?? 0) }}" enctype="multipart/form-data">@csrf @method('PUT')
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
      <div><label>Nama Kategori</label><input name="nama_kategori" value="{{ $row->nama_kategori ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
    </div>
    <div style="margin-top:12px;">
      <label>Gambar Kategori (opsional)</label>
      <input type="file" accept="image/*" name="gambar" style="display:block;margin-top:6px;">
      @if(!empty($row->gambar))
        <div style="margin-top:8px;">
          @php
            $g = trim($row->gambar ?? '');
            if (str_starts_with($g, 'http://') || str_starts_with($g, 'https://')) {
              $src = $g;
            } elseif (str_starts_with($g, 'storage/')) {
              $src = asset(ltrim($g, '/'));
            } else {
              $src = asset('storage/'.ltrim($g, '/'));
            }
          @endphp
          <img src="{{ $src }}" alt="Gambar kategori" style="height:72px;border-radius:8px;border:1px solid #e5e7eb;">
        </div>
      @endif
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection
