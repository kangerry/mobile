@php($title = 'Gateway Logs: Topup VA')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Gateway Logs: Topup VA</div>
  <form method="GET" action="{{ route('gateway-logs.index') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <select name="status" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      <option value="">Semua Status</option>
      <option value="PENDING" @if(request('status')==='PENDING') selected @endif>PENDING</option>
      <option value="PAID" @if(request('status')==='PAID') selected @endif>PAID</option>
    </select>
    <input name="kop" value="{{ request('kop') }}" placeholder="Koperasi ID" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px;width:160px">
    <input type="date" name="from" value="{{ request('from') }}" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px">
    <input type="date" name="to" value="{{ request('to') }}" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px">
    <input name="q" value="{{ request('q') }}" placeholder="Cari invoice/VA" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px;width:200px">
    <input name="min_amount" value="{{ request('min_amount') }}" placeholder="Minimal Rp" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px;width:140px">
    <input name="max_amount" value="{{ request('max_amount') }}" placeholder="Maksimal Rp" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px;width:140px">
    <select name="per_page" style="padding:10px;border:1px solid #e5e7eb;border-radius:8px">
      @foreach([25,50,100,200] as $n)
      <option value="{{ $n }}" @if((int)request('per_page',50)===$n) selected @endif>{{ $n }}/hal</option>
      @endforeach
    </select>
    <button class="btn-brand"><i class="fa fa-search"></i> Filter</button>
    <a class="btn-brand" href="{{ route('gateway-logs.export', request()->query()) }}"><i class="fa fa-download"></i> Export CSV</a>
  </form>
</div>
<div class="card">
  <div class="table-scroll">
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Koperasi</th>
          <th>Invoice</th>
          <th>VA</th>
          <th>Jumlah</th>
          <th>Status</th>
          <th>Diperbarui</th>
          <th>Payload</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $i)
        <tr>
          <td>{{ $i->id }}</td>
          <td>{{ $i->nama_koperasi }}</td>
          <td style="font-family:monospace">{{ $i->nomor_invoice }}</td>
          <td style="font-family:monospace">{{ $i->external_id }}</td>
          <td>Rp {{ number_format((float)$i->jumlah,0,',','.') }}</td>
          <td><span class="badge {{ $i->status==='PAID' ? 'green' : 'yellow' }}">{{ $i->status }}</span></td>
          <td>{{ $i->updated_at }}</td>
          <td><details><summary>Lihat</summary><pre style="white-space:pre-wrap">{{ $i->response_payload }}</pre></details></td>
        </tr>
        @empty
        <tr><td colspan="8">Belum ada data</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<div style="margin-top:12px">
  {{ $items->links() }}
</div>
@endsection
