@php($title = 'Monitoring Driver')
@extends('layouts.app')
@section('content')
<div class="card" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
  <div class="card-title">Monitoring Driver</div>
  <div class="muted">Posisi driver online</div>
  </div>
<div class="card" style="margin-bottom:12px;">
  <div id="map" style="width:100%;height:420px;border-radius:10px;border:1px solid var(--color-border)"></div>
  <div style="margin-top:12px;">
    <table class="table">
      <thead><tr><th>Nama</th><th>Status</th><th>Lat</th><th>Lng</th></tr></thead>
      <tbody>
      @foreach(($drivers ?? []) as $d)
        <tr>
          <td>{{ $d->nama_driver }}</td>
          <td><span class="badge {{ $d->status_online ? 'success':'warning' }}">{{ $d->status_online ? 'Online':'Offline' }}</span></td>
          <td>{{ $d->latitude_terakhir ?? '-' }}</td>
          <td>{{ $d->longitude_terakhir ?? '-' }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  </div>
<script src="/maps.js"></script>
<script>
let map;
let markers = {};
function initMap(){
  map = new google.maps.Map(document.getElementById('map'), {center: {lat: -6.9175, lng: 107.6191}, zoom: 12});
}
function refreshPositions(){
  fetch('{{ route('driver.positions') }}', {credentials:'same-origin'})
    .then(r=>r.json())
    .then(list=>{
      list.forEach(d=>{
        const id = d.id;
        const pos = {lat: d.lat, lng: d.lng};
        if (!markers[id]) {
          markers[id] = new google.maps.Marker({position: pos, map, title: d.name});
        } else {
          markers[id].setPosition(pos);
        }
      });
    });
}
function ready(){
  if (window.google && window.google.maps) {
    initMap();
    refreshPositions();
    setInterval(refreshPositions, 10000);
  } else {
    setTimeout(ready, 500);
  }
}
ready();
</script>
@endsection
