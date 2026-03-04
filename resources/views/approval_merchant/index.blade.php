@extends('layouts.app', ['title' => 'Approval Seller'])

@section('content')
<div class="card">
  <div class="card-header">
    <div class="title">Approval Seller</div>
  </div>
  <div class="card-body">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nama Toko</th>
          <th>Pemilik (Anggota)</th>
          <th>Koperasi</th>
          <th>Alamat</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse($items as $i)
        <tr>
          <td>{{ $i->id }}</td>
          <td>{{ $i->nama_toko }}</td>
          <td>{{ $i->nama_anggota ?? '-' }}</td>
          <td>{{ $i->nama_koperasi }}</td>
          <td>{{ $i->alamat }}</td>
          <td style="display:flex;gap:8px;">
            <form method="post" action="{{ route('approval-merchant.approve', $i->id) }}">
              @csrf
              <button type="submit" class="btn btn-primary">Approve</button>
            </form>
            <form method="post" action="{{ route('approval-merchant.reject', $i->id) }}">
              @csrf
              <button type="submit" class="btn btn-danger">Reject</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="6">Belum ada pengajuan</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  </div>
@endsection

