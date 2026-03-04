@php($title = 'Kategori Produk')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Kategori</div>
  <a class="btn-brand" href="{{ route('kategori-produk.create') }}"><i class="fa fa-plus"></i> Tambah</a>
  </div>
<div class="card">
  <table class="table">
    <thead><tr><th>Nama Kategori</th><th>Koperasi</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse(($items ?? []) as $i)
      <tr>
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
      <tr><td colspan="3">Belum ada data</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection

