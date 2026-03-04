@php($title = 'Approval Driver')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Pengajuan Driver</div>
  <div class="muted">Driver yang mendaftar via aplikasi</div>
  </div>
<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th>Nama</th>
        <th>Email</th>
        <th>Kendaraan</th>
        <th>Plat</th>
        <th>Koperasi</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse(($items ?? []) as $i)
      <tr>
        <td>{{ $i->nama_driver }}</td>
        <td>{{ $i->email ?? '-' }}</td>
        <td>{{ $i->jenis_kendaraan ?? '-' }}</td>
        <td>{{ $i->plat_nomor ?? '-' }}</td>
        <td>{{ $i->nama_koperasi }}</td>
        <td class="actions">
          <form method="POST" action="{{ route('approval-driver.approve', $i->id) }}" style="display:inline">@csrf
            <button class="btn-brand" type="submit"><i class="fa fa-check"></i> Setujui</button>
          </form>
          <form method="POST" action="{{ route('approval-driver.reject', $i->id) }}" onsubmit="return confirm('Tolak pengajuan ini?')" style="display:inline">@csrf
            <button class="btn-brand" type="submit"><i class="fa fa-times"></i> Tolak</button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="6">Tidak ada pengajuan</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection

