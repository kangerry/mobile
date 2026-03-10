@php($title = 'Hak Akses: Role')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Role</div>
  <a class="btn-brand" href="{{ route('access-roles.create') }}"><i class="fa fa-plus"></i> Tambah Role</a>
  <a class="btn-brand" href="{{ route('permissions.index') }}"><i class="fa fa-key"></i> Permissions</a>
  <a class="btn-brand" href="{{ route('users.index') }}"><i class="fa fa-user-gear"></i> Pengguna</a>
  </div>
<div class="card">
  <table class="table">
    <thead>
      <tr><th>Nama</th><th>Jumlah User</th><th>Aksi</th></tr>
    </thead>
    <tbody>
      @forelse(($roles ?? []) as $r)
      <tr>
        <td>{{ $r->name }}</td>
        <td>{{ $counts[$r->id] ?? 0 }}</td>
        <td class="actions">
          <a class="btn-brand" href="{{ route('access-roles.edit', $r->id) }}"><i class="fa fa-pen"></i> Edit</a>
          @if($r->name !== 'superadmin')
          <form method="POST" action="{{ route('access-roles.destroy', $r->id) }}" onsubmit="return confirm('Hapus role?')">
            @csrf @method('DELETE')
            <button class="btn-brand" type="submit"><i class="fa fa-trash"></i> Hapus</button>
          </form>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="3">Belum ada data</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection

