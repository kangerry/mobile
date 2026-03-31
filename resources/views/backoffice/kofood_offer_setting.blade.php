@extends('layouts.app')
@section('title', 'Pengaturan KoFood • Driver Offer')
@section('content')
<div class="container">
    <h2>Pengaturan KoFood • Driver Offer</h2>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <form method="post" action="{{ route('kofood-offer.setting.update') }}" style="max-width:640px">
        @csrf
        <div class="form-group">
            <label>Durasi Expire Offer (menit)</label>
            <input type="number" name="expire_minutes" min="1" max="120" value="{{ old('expire_minutes', $expire) }}" class="form-control" required>
            @error('expire_minutes')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Maksimum Putaran Re-Offer</label>
            <input type="number" name="max_rounds" min="1" max="10" value="{{ old('max_rounds', $rounds) }}" class="form-control" required>
            @error('max_rounds')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <div class="form-group">
            <label>Jumlah Driver Terdekat (Top-N)</label>
            <input type="number" name="top_n" min="1" max="50" value="{{ old('top_n', $topN) }}" class="form-control" required>
            @error('top_n')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <div class="form-actions" style="margin-top:16px">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('dashboard') }}" class="btn btn-secondary">Batal</a>
        </div>
        <p class="muted" style="margin-top:12px">Perubahan akan ditulis ke .env dan berlaku untuk request berikutnya.</p>
    </form>
</div>
@endsection
