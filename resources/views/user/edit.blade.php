@php($title = 'Edit Pengguna')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Pengguna</div>
  <form method="POST" action="{{ route('users.update', $row->id ?? 0) }}">
    @csrf @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div>
        <label>Nama</label>
        <input name="name" value="{{ old('name', $row->name ?? '') }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>Email</label>
        <input name="email" value="{{ old('email', $row->email ?? '') }}" type="email" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>Password Baru (kosongkan jika tidak diganti)</label>
        <input name="password" type="password" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>Role</label>
        <select name="role" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          @foreach(($roles ?? collect()) as $r)
          <option value="{{ $r->name }}" @if(old('role',$role)===$r->name) selected @endif>{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div style="grid-column:1/-1;">
        <label>Koperasi (pilih jika role=admin)</label>
        <select name="koperasi_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          <option value="">-</option>
          @foreach(($koperasis ?? []) as $k)
          <option value="{{ $k->id }}" @if((int)old('koperasi_id', $row->koperasi_id ?? 0)===(int)$k->id) selected @endif>{{ $k->nama_koperasi }}</option>
          @endforeach
        </select>
      </div>
    </div>
    <div style="margin-top:12px;">
      <button class="btn-brand">Simpan</button>
      <a class="btn-brand" href="{{ route('users.index') }}">Batal</a>
    </div>
  </form>
</div>
@endsection
