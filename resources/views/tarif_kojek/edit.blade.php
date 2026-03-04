@php($title = 'Edit Tarif Kojek')
@extends('layouts.app')
@section('content')
<div class="card">
  <div class="card-title" style="margin-bottom:12px;">Edit Tarif Kojek</div>
  <form method="POST" action="{{ route('tarif-kojek.update', $item->id) }}">@csrf @method('PUT')
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;">
      <div>
        <label>Koperasi</label>
        @if(auth()->check() && auth()->user()->hasRole('superadmin'))
          <select name="koperasi_id" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px">
            @foreach($koperasis as $k)
            <option value="{{ $k->id }}" @if($k->id==$item->koperasi_id) selected @endif>{{ $k->nama_koperasi }}</option>
            @endforeach
          </select>
        @else
          <input type="hidden" name="koperasi_id" value="{{ auth()->user()->koperasi_id }}">
          <div class="form-static">{{ optional(($koperasis ?? collect())->first())->nama_koperasi }}</div>
        @endif
      </div>
      <div><label>Start KM</label><input name="start_km" type="number" step="0.01" value="{{ $item->start_km }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>End KM</label><input name="end_km" type="number" step="0.01" value="{{ $item->end_km }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Biaya Dasar</label><input name="biaya_dasar" type="number" step="0.01" value="{{ $item->biaya_dasar }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Biaya per KM</label><input name="biaya_per_km" type="number" step="0.01" value="{{ $item->biaya_per_km }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div><label>Min Fare</label><input name="min_fare" type="number" step="0.01" value="{{ $item->min_fare }}" style="width:100%;padding:10px;border:1px solid #e5e7eb;border-radius:8px"></div>
      <div style="grid-column:1/-1;display:flex;align-items:center;gap:8px;">
        <input type="checkbox" name="aktif" @if($item->aktif) checked @endif> <span>Aktif</span>
      </div>
    </div>
    <div style="margin-top:12px;">
      <button class="btn-brand">Simpan</button>
      <a class="btn-brand" href="{{ route('tarif-kojek.index') }}">Batal</a>
    </div>
  </form>
</div>
@endsection
