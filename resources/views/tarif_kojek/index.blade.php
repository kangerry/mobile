@php($title = 'Tarif Kojek')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Tarif Kojek</div>
  <a class="btn-brand" href="{{ route('tarif-kojek.create') }}"><i class="fa fa-plus"></i> Tambah</a>
</div>
<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th>Koperasi</th><th>Start KM</th><th>End KM</th><th>Dasar</th><th>/KM</th><th>Min Fare</th><th>Aktif</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
    @forelse($items as $i)
      <tr>
        <td>{{ $i->nama_koperasi }}</td>
        <td>{{ number_format($i->start_km,2) }}</td>
        <td>{{ number_format($i->end_km,2) }}</td>
        <td>{{ number_format($i->biaya_dasar,0) }}</td>
        <td>{{ number_format($i->biaya_per_km,0) }}</td>
        <td>{{ number_format($i->min_fare,0) }}</td>
        <td>{!! $i->aktif ? '<span class="badge success">Aktif</span>' : '<span class="badge warning">Nonaktif</span>' !!}</td>
        <td class="actions">
          <a class="btn-brand" href="{{ route('tarif-kojek.edit', $i->id) }}"><i class="fa fa-pen"></i> Edit</a>
          <form method="POST" action="{{ route('tarif-kojek.destroy', $i->id) }}" onsubmit="return confirm('Hapus data?')">
            @csrf @method('DELETE')
            <button class="btn-brand" type="submit"><i class="fa fa-trash"></i> Hapus</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="8">Belum ada data</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection

