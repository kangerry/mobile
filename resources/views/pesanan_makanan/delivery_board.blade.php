@php($title = 'Pesanan Delivery')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Pesanan Delivery</div>
  <div class="muted">Tipe pengiriman delivery</div>
  </div>
<div class="card">
  <table class="table">
    <thead><tr><th>Nomor</th><th>Toko</th><th>Total</th><th>Driver</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse(($items ?? []) as $i)
      <tr>
        <td>{{ $i->nomor_pesanan }}</td>
        <td>{{ $i->nama_toko }}</td>
        <td>{{ number_format($i->total_bayar ?? 0, 0, ',', '.') }}</td>
        <td>{{ $i->nama_driver ?? '-' }}</td>
        <td><span class="badge">{{ $i->status }}</span></td>
        <td class="actions">
          @if(empty($i->driver_id))
          <form method="POST" action="{{ route('pesanan-makanan.assign-driver', $i->id) }}" style="display:inline">@csrf
            <select name="driver_id" style="padding:6px 8px;border:1px solid #e5e7eb;border-radius:8px">
              @foreach(($drivers ?? []) as $d)
              <option value="{{ $d->id }}">{{ $d->nama_driver }}</option>
              @endforeach
            </select>
            <button class="btn-brand" type="submit"><i class="fa fa-user"></i> Tetapkan</button>
          </form>
          @endif
          @if(($i->status ?? '') === 'dikirim')
          <form method="POST" action="{{ route('pesanan-makanan.complete', $i->id) }}" style="display:inline">@csrf
            <button class="btn-brand" type="submit"><i class="fa fa-check"></i> Selesai</button>
          </form>
          @endif
          <a class="btn-brand" target="_blank" href="https://www.google.com/maps/search/?api=1&query={{ urlencode((string)($i->alamat_tujuan ?? '')) }}"><i class="fa fa-map"></i> Tujuan</a>
        </td>
      </tr>
      @empty
      <tr><td colspan="6">Belum ada data</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
