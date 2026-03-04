@php($title = 'Tambah Tarif Delivery Toko')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Form Tarif Delivery</div>
  <form method="POST" action="{{ route('tarif-delivery-toko.store') }}">@csrf
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
      <div>
        <label>Koperasi</label>
        @if(auth()->check() && auth()->user()->hasRole('superadmin'))
          <select name="koperasi_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
            @foreach($koperasis as $k)
            <option value="{{ $k->id }}">{{ $k->nama_koperasi }}</option>
            @endforeach
          </select>
        @else
          <input type="hidden" name="koperasi_id" value="{{ auth()->user()->koperasi_id }}">
          <div class="form-static">{{ optional(($koperasis ?? collect())->first())->nama_koperasi }}</div>
        @endif
      </div>
      <div>
        <label>Merchant (opsional)</label>
        @if(auth()->check() && auth()->user()->hasRole('superadmin'))
          <select name="merchant_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
            <option value="">Default Koperasi</option>
            @foreach($merchants as $m)
            <option value="{{ $m->id }}">{{ $m->nama_toko }}</option>
            @endforeach
          </select>
        @else
          <select name="merchant_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
            <option value="">Default Koperasi</option>
            @foreach($merchants as $m)
            <option value="{{ $m->id }}">{{ $m->nama_toko }}</option>
            @endforeach
          </select>
        @endif
      </div>
      <div><label>Start KM</label><input name="start_km" type="number" step="0.01" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>End KM</label><input name="end_km" type="number" step="0.01" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Biaya Dasar</label><input name="biaya_dasar" type="number" step="0.01" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Biaya per KM</label><input name="biaya_per_km" type="number" step="0.01" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Min Fare</label><input name="min_fare" type="number" step="0.01" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="aktif" checked> <span>Aktif</span>
      </div>
    </div>
    <div style="margin-top:12px;">
      <button class="btn-brand">Simpan</button>
      <a class="btn-brand" href="{{ route('tarif-delivery-toko.index') }}">Batal</a>
    </div>
  </form>
</div>
@endsection
