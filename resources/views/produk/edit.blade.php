@php($title = 'Edit Produk')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Produk</div>
  <form method="POST" action="{{ route('produk.update', $produk->id ?? ($id ?? 1)) }}" enctype="multipart/form-data">@csrf @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Merchant</label>
        <select name="merchant_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          @foreach($merchants as $m)
          <option value="{{ $m->id }}" @selected(($produk->merchant_id ?? null) == $m->id)>{{ $m->nama_toko }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label>Kategori</label>
        <select name="kategori_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          <option value="">- Pilih Kategori -</option>
          @foreach(($categories ?? []) as $c)
          <option value="{{ $c->id }}" @selected(($produk->kategori_id ?? null) == $c->id)>{{ $c->nama_kategori }}</option>
          @endforeach
        </select>
      </div>
      <div><label>Nama</label><input name="nama" value="{{ $produk->nama_produk ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Harga</label><input name="harga" type="number" value="{{ $produk->harga ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;"><label>Deskripsi</label><textarea name="deskripsi" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">{{ $produk->deskripsi ?? '' }}</textarea></div>
      <div style="grid-column:1/-1;">
        <label>Foto Produk (maks 5)</label>
        <input type="file" name="foto[]" multiple accept="image/*" data-preview-target="#preview-foto-edit-new" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
        <div style="color:#64748b;font-size:12px;margin-top:4px;">Tambah foto baru tanpa menghapus yang sudah ada. Maks total 5 foto.</div>
        <div id="preview-foto-edit-new" class="preview-grid"></div>
        @if(!empty($fotos))
        <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
          @foreach($fotos as $f)
            <img src="{{ asset('storage/'.$f->url_foto) }}" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb">
          @endforeach
        </div>
        @endif
      </div>
      <div>
        <label>Upload Video (opsional)</label>
        <input type="file" name="video_file" accept="video/*" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>Link Video (opsional)</label>
        <input type="url" name="video_url" value="{{ $produk->video_url ?? '' }}" placeholder="https://youtube.com/..." style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection
