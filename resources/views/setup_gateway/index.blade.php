@php($title = 'Payment Gateway')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Pengaturan Payment Gateway (DOKU)</div>
  <form method="GET" action="{{ route('setup-gateway.index') }}" style="display:flex;gap:8px;align-items:center;">
    <input name="kode_koperasi" value="{{ $values['kode_koperasi'] ?? '' }}" placeholder="Masukkan kode_koperasi" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px">
    <button class="btn-brand"><i class="fa fa-search"></i> Muat</button>
  </form>
</div>
@if (session('status'))
<div class="alert" style="margin-bottom:12px;background:#f1f5f9;border:1px solid #e5e7eb;padding:10px;border-radius:8px;">
  {{ session('status') }}
  </div>
@endif
@if ($errors->any())
<div class="alert" style="margin-bottom:12px;background:#fff1f2;border:1px solid #fecdd3;padding:10px;border-radius:8px;color:#991b1b;">
  <div style="font-weight:700;margin-bottom:6px;">Gagal menyimpan:</div>
  <ul style="margin:0;padding-left:18px;">
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
</div>
@endif
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Pengaturan Payment Gateway (DOKU)</div>
  <form method="POST" action="{{ route('setup-gateway.store') }}">@csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
      <div style="grid-column:1/-1;">
        <label>Kode Koperasi</label>
        <input name="kode_koperasi" value="{{ $values['kode_koperasi'] ?? '' }}" placeholder="Wajib diisi" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>Environment</label>
        <select name="DOKU_ENV" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
          <option value="sandbox" {{ ($values['DOKU_ENV'] ?? '') === 'sandbox' ? 'selected' : '' }}>Sandbox</option>
          <option value="production" {{ ($values['DOKU_ENV'] ?? '') === 'production' ? 'selected' : '' }}>Production</option>
        </select>
      </div>
      <div>
        <label>DOKU Base URL</label>
        <input name="DOKU_BASE_URL" value="{{ $values['DOKU_BASE_URL'] ?? '' }}" placeholder="https://api-sandbox.doku.com" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>DOKU Client ID</label>
        <input name="DOKU_CLIENT_ID" value="{{ $values['DOKU_CLIENT_ID'] ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div>
        <label>DOKU Secret Key</label>
        <input name="DOKU_SECRET_KEY" value="{{ $values['DOKU_SECRET_KEY'] ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div style="grid-column:1/-1;">
        <label>DOKU API Key</label>
        <input name="DOKU_API_KEY" value="{{ $values['DOKU_API_KEY'] ?? '' }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      </div>
      <div style="grid-column:1/-1;">
        <label>DOKU Private Key (RSA)</label>
        <textarea name="DOKU_PRIVATE_KEY" rows="6" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">{{ $values['DOKU_PRIVATE_KEY'] ?? '' }}</textarea>
      </div>
      <div style="grid-column:1/-1;">
        <label>DOKU Public Key</label>
        <textarea name="DOKU_PUBLIC_KEY" rows="6" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">{{ $values['DOKU_PUBLIC_KEY'] ?? '' }}</textarea>
      </div>
    </div>
    <div style="margin-top:12px;display:flex;gap:8px;align-items:center;">
      <button class="btn-brand"><i class="fa fa-save"></i> Simpan untuk Koperasi</button>
      <div class="muted">Data akan tersimpan per koperasi berdasarkan kode_koperasi.</div>
    </div>
  </form>
  <form method="POST" action="{{ route('setup-gateway.test') }}" style="margin-top:12px;display:flex;gap:8px;align-items:center;">@csrf
    <input type="hidden" name="kode_koperasi" value="{{ $values['kode_koperasi'] ?? '' }}">
    <button class="btn-brand" {{ empty($values['kode_koperasi']) ? 'disabled' : '' }}>
      <i class="fa fa-plug"></i> Test Connection
    </button>
    <div class="muted">Menguji konektivitas ke Base URL DOKU untuk koperasi ini.</div>
  </form>
  <form method="POST" action="{{ route('setup-gateway.advanced') }}" style="margin-top:12px;display:flex;gap:8px;align-items:center;">@csrf
    <input type="hidden" name="kode_koperasi" value="{{ $values['kode_koperasi'] ?? '' }}">
    <button class="btn-brand" {{ empty($values['kode_koperasi']) ? 'disabled' : '' }}>
      <i class="fa fa-shield-halved"></i> Advanced Test
    </button>
    <div class="muted">Menguji signature, Authorization, dan validitas Public Key.</div>
  </form>
</div>
@endsection
