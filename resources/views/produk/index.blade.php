@php($title = 'Produk Makanan')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Produk</div>
  <a class="btn-brand" href="{{ route('produk.create') }}"><i class="fa fa-plus"></i> Tambah</a>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>Foto</th><th>Nama</th><th>Merchant</th><th>Harga</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse($items ?? [] as $p)
      <tr>
        <td>
          @if (!empty($p->foto_utama))
            <img src="{{ url('/api/v1/kofood/product-image?path='.rawurlencode($p->foto_utama).'&koperasi_id='.(int)($p->koperasi_id ?? 0)) }}"
                 alt="foto"
                 width="60"
                 height="60"
                 style="object-fit:cover;border-radius:6px;">
          @else
            -
          @endif
        </td>
        <td>{{ $p->nama_produk }}</td>
        <td>{{ $p->nama_toko }}</td>
        <td>{{ number_format($p->harga,0,',','.') }}</td>
        <td><span class="badge {{ $p->status_tersedia ? 'success':'danger' }}">{{ $p->status_tersedia ? 'Tersedia' : 'Nonaktif' }}</span></td>
        <td class="actions">
          <a class="btn-brand" href="{{ route('produk.edit', $p->id) }}"><i class="fa fa-pen"></i> Edit</a>
          <form method="POST" action="{{ route('produk.destroy', $p->id) }}" onsubmit="return confirm('Hapus data?')">@csrf @method('DELETE')
            <button class="btn-brand" type="submit"><i class="fa fa-trash"></i> Hapus</button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="6">Belum ada data</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
