@php($title = 'Merchant')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Merchant</div>
  <a class="btn-brand" href="{{ route('merchant.create') }}"><i class="fa fa-plus"></i> Tambah</a>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>Nama Toko</th><th>Pemilik</th><th>Kota</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse(($items ?? []) as $i)
      <tr>
        <td>{{ $i->nama_toko }}</td>
        <td>{{ $i->nama_pemilik }}</td>
        <td>{{ $i->kota }}</td>
        <td><span class="badge {{ ($i->status ?? 'aktif') === 'aktif' ? 'success':'danger' }}">{{ $i->status }}</span></td>
        <td class="actions">
          <a class="btn-brand" href="{{ route('merchant.edit', $i->id) }}"><i class="fa fa-pen"></i> Edit</a>
          <form method="POST" action="{{ route('merchant.destroy', $i->id) }}" onsubmit="return confirm('Hapus data?')">@csrf @method('DELETE')
            <button class="btn-brand" type="submit"><i class="fa fa-trash"></i> Hapus</button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="5">Belum ada data</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
