@php($title = 'Pengguna')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Pengguna</div>
  <a class="btn-brand" href="{{ route('users.create') }}"><i class="fa fa-plus"></i> Tambah</a>
</div>
<div class="card">
  <table class="table">
    <thead>
      <tr><th>Nama</th><th>Email</th><th>Role</th><th>Koperasi</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      @forelse(($items ?? []) as $i)
      <tr>
        <td>{{ $i->name }}</td>
        <td>{{ $i->email }}</td>
        <td>{{ $i->role_name ?? 'admin' }}</td>
        <td>{{ $i->nama_koperasi ?? '-' }}</td>
        <td class="actions">
          <a class="btn-brand" href="{{ route('users.edit', $i->id) }}"><i class="fa fa-pen"></i> Edit</a>
          <form method="POST" action="{{ route('users.destroy', $i->id) }}" onsubmit="return confirm('Hapus user?')">
            @csrf @method('DELETE')
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

