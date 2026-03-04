@php($title = 'Edit Gateway')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Gateway</div>
  <form method="POST" action="{{ route('setup-gateway.update', $id ?? 1) }}">@csrf @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div><label>Nama</label><input name="nama_gateway" value="Midtrans" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Mode</label><input name="mode" value="sandbox" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection

