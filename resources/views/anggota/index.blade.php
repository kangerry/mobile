@php($title = 'Anggota')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Anggota</div>
  <a class="btn-brand" href="{{ route('anggota.create') }}"><i class="fa fa-plus"></i> Tambah</a>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>Nomor</th><th>Nama</th><th>Email</th><th>Telepon</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse(($items ?? []) as $i)
      <tr>
        <td>{{ $i->nomor_anggota }}</td>
        <td>{{ $i->nama_anggota }}</td>
        <td>{{ $i->email }}</td>
        <td>{{ $i->telepon }}</td>
        <td><span class="badge {{ ($i->status ?? 'aktif') === 'aktif' ? 'success':'danger' }}">{{ $i->status }}</span></td>
        <td class="actions">
          <a class="btn-brand" href="{{ route('anggota.edit', $i->id) }}"><i class="fa fa-pen"></i> Edit</a>
          <form method="POST" action="{{ route('anggota.destroy', $i->id) }}" onsubmit="return confirm('Hapus data?')">@csrf @method('DELETE')
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
