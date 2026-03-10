@php($title = 'Edit Role')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Role</div>
  <form method="POST" action="{{ route('access-roles.update', $role->id ?? 0) }}">
    @csrf @method('PUT')
    <div>
      <label>Nama Role</label>
      <input name="name" value="{{ old('name', $role->name ?? '') }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
    </div>
    <div style="margin-top:12px;">
      <div class="card-title" style="margin-bottom:8px;">Permissions</div>
      <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;">
        @foreach(($perms ?? []) as $p)
        <label style="display:flex;gap:8px;align-items:center;border:1px solid #e5e7eb;border-radius:8px;padding:8px;">
          <input type="checkbox" name="permissions[]" value="{{ $p->name }}" @if(in_array($p->name, $selected ?? [])) checked @endif>
          <span>{{ $p->name }}</span>
        </label>
        @endforeach
      </div>
    </div>
    <div style="margin-top:12px;">
      <button class="btn-brand">Simpan</button>
      <a class="btn-brand" href="{{ route('access-roles.index') }}">Kembali</a>
      <a class="btn-brand" href="{{ route('permissions.index') }}"><i class="fa fa-key"></i> Kelola Permissions</a>
    </div>
  </form>
</div>
@endsection

