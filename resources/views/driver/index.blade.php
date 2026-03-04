@php($title = 'Driver')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Driver</div>
  <a class="btn-brand" href="{{ route('driver.create') }}"><i class="fa fa-plus"></i> Tambah</a>
  </div>
<div class="card">
  <table class="table">
    <thead><tr><th>Nama</th><th>Kendaraan</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse(($items ?? []) as $i)
      <tr>
        <td>{{ $i->nama_driver }}</td>
        <td>{{ $i->jenis_kendaraan ?? '-' }}</td>
        <td><span class="badge {{ ($i->terverifikasi ?? false) ? 'success':'warning' }}">{{ ($i->terverifikasi ?? false) ? 'Terverifikasi':'Belum' }}</span></td>
        <td class="actions">
          <a class="btn-brand" href="{{ route('driver.edit', $i->id) }}"><i class="fa fa-pen"></i> Edit</a>
          <form method="POST" action="{{ route('driver.destroy', $i->id) }}" onsubmit="return confirm('Hapus data?')">@csrf @method('DELETE')
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
