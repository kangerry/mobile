@php($title = 'Koperasi')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Daftar Koperasi</div>
  <a class="btn-brand" href="{{ route('koperasi.create') }}"><i class="fa fa-plus"></i> Tambah</a>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>Kode</th><th>Nama</th><th>Alamat</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
      <tr><td>KOP001</td><td>Koperasi Maju</td><td>Jl. Mawar No.1</td><td><span class="badge success">Aktif</span></td>
      <td class="actions">
        <a class="btn-brand" href="{{ route('koperasi.edit', 1) }}"><i class="fa fa-pen"></i> Edit</a>
        <form method="POST" action="#" onsubmit="return confirm('Hapus data?')">@csrf @method('DELETE')
          <button class="btn-brand" type="submit"><i class="fa fa-trash"></i> Hapus</button>
        </form>
      </td></tr>
    </tbody>
  </table>
</div>
@endsection

