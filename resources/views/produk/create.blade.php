@php($title = 'Tambah Produk')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Form Produk</div>
  <form method="POST" action="{{ route('produk.store') }}" enctype="multipart/form-data">@csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Merchant</label>
        <select name="merchant_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          @foreach($merchants as $m)
          <option value="{{ $m->id }}">{{ $m->nama_toko }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Kategori</label>
        <select name="kategori_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          <option value="">- Pilih Kategori -</option>
          @foreach(($categories ?? []) as $c)
          <option value="{{ $c->id }}">{{ $c->nama_kategori }}</option>
          @endforeach
        </select>
      </div>
      <div><label>Nama</label><input name="nama" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Harga</label><input name="harga" type="number" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;"><label>Deskripsi</label><textarea name="deskripsi" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></textarea></div>
      <div style="grid-column:1/-1;">
        <label>Foto Produk (maks 5)</label>
        <input type="file" name="foto[]" multiple accept="image/*" data-preview-target="#preview-foto-create" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
        <div style="color:#64748b;font-size:12px;margin-top:4px;">Unggah hingga 5 foto. Format: JPG/PNG/WEBP, maks 2MB/foto.</div>
        <div id="preview-foto-create" class="preview-grid"></div>
      </div>
      <div>
        <label>Upload Video (opsional)</label>
        <input type="file" name="video_file" accept="video/*" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>Link Video (opsional)</label>
        <input type="url" name="video_url" placeholder="https://youtube.com/..." style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection
