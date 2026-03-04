@php($title = 'Tambah Gateway')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Form Gateway</div>
  <form method="POST" action="{{ route('setup-gateway.store') }}">@csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div><label>Nama</label><input name="nama_gateway" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Mode</label><input name="mode" value="sandbox" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;"><label>API Key</label><input name="api_key" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
    </div>
    <div style="margin-top:12px;"><button class="btn-brand">Simpan</button></div>
  </form>
</div>
@endsection

