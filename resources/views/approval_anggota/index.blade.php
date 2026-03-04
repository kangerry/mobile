@extends('layouts.app', ['title' => 'Approval Anggota'])

@section('content')
<div class="card">
  <div class="card-header">
    <div class="title">Approval Anggota</div>
  </div>
  <div class="card-body">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nomor</th>
          <th>Nama</th>
          <th>Email</th>
          <th>Telepon</th>
          <th>Koperasi</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      @forelse($items as $i)
        <tr>
          <td>{{ $i->id }}</td>
          <td>{{ $i->nomor_anggota }}</td>
          <td>{{ $i->nama_anggota }}</td>
          <td>{{ $i->email }}</td>
          <td>{{ $i->telepon }}</td>
          <td>{{ $i->nama_koperasi }}</td>
          <td style="display:flex;gap:8px;">
            <form method="post" action="{{ route('approval-anggota.approve', $i->id) }}">
              @csrf
              <button type="submit" class="btn btn-primary">Approve</button>
            </form>
            <form method="post" action="{{ route('approval-anggota.reject', $i->id) }}">
              @csrf
              <button type="submit" class="btn btn-danger">Reject</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7">Belum ada pengajuan</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
