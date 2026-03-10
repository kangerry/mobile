@php($title = 'Tambah Role')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Tambah Role</div>
  <form method="POST" action="{{ route('access-roles.store') }}">
    @csrf
    <div>
      <label>Nama Role</label>
      <input name="name" value="{{ old('name') }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
    </div>
    <div style="margin-top:12px;">
      <button class="btn-brand">Simpan</button>
      <a class="btn-brand" href="{{ route('access-roles.index') }}">Batal</a>
    </div>
  </form>
  <div class="muted" style="margin-top:10px;">Tambahkan permissions di halaman Permissions lalu kelola mapping di Edit Role.</div>
  <div style="margin-top:8px;">
    <a class="btn-brand" href="{{ route('permissions.index') }}"><i class="fa fa-key"></i> Kelola Permissions</a>
  </div>
  </div>
@endsection

