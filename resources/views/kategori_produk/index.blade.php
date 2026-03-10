@php($title = 'Kategori Produk')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Kategori</div>
  <a class="btn-brand" href="{{ route('kategori-produk.create') }}"><i class="fa fa-plus"></i> Tambah</a>
  </div>
<div class="card">
  <table class="table">
    <thead><tr><th>Gambar</th><th>Nama Kategori</th><th>Koperasi</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse(($items ?? []) as $i)
      <tr>
        <td>
          @if(!empty($i->gambar))
          <img src="{{ asset('storage/'.ltrim($i->gambar, '/')) }}" style="height:48px;border-radius:6px;border:1px solid #e5e7eb;">
          @else
          <span class="muted">-</span>
          @endif
        </td>
        <td>{{ $i->nama_kategori }}</td>
        <td>{{ $i->nama_koperasi }}</td>
        <td class="actions">
          <a class="btn-brand" href="{{ route('kategori-produk.edit', $i->id) }}"><i class="fa fa-pen"></i> Edit</a>
          <form method="POST" action="{{ route('kategori-produk.destroy', $i->id) }}" onsubmit="return confirm('Hapus data?')">@csrf @method('DELETE')
            <button class="btn-brand" type="submit"><i class="fa fa-trash"></i> Hapus</button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="4">Belum ada data</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
