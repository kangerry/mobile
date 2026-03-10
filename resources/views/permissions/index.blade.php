@php($title = 'Hak Akses: Permissions')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Permissions</div>
  <a class="btn-brand" href="{{ route('access-roles.index') }}"><i class="fa fa-users-gear"></i> Role</a>
</div>
<div class="card" style="margin-bottom:12px;">
  <form method="POST" action="{{ route('permissions.store') }}" style="display:flex;gap:8px;align-items:center;">
    @csrf
    <input name="name" placeholder="contoh: koperasi.manage" style="flex:1;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
    <button class="btn-brand"><i class="fa fa-plus"></i> Tambah</button>
  </form>
  <div class="muted" style="margin-top:8px;">Gunakan format slug sederhana, contoh: koperasi.manage, anggota.manage, access.all</div>
  </div>
<div class="card">
  <table class="table">
    <thead><tr><th>Nama</th><th>Aksi</th></tr></thead>
    <tbody>
      @forelse(($perms ?? []) as $p)
      <tr>
        <td>{{ $p->name }}</td>
        <td class="actions">
          @if($p->name !== 'access.all')
          <form method="POST" action="{{ route('permissions.destroy', $p->id) }}" onsubmit="return confirm('Hapus permission?')">
            @csrf @method('DELETE')
            <button class="btn-brand" type="submit"><i class="fa fa-trash"></i> Hapus</button>
          </form>
          @endif
        </td>
      </tr>
      @empty
      <tr><td colspan="2">Belum ada permission</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection

