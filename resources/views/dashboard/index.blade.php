@php($title = 'Dashboard')
@extends('layouts.app')
@section('styles')
<link rel="stylesheet" href="{{ asset('assets/css/dashboard.css') }}">
@endsection
@section('content')
<div class="dashboard-layout">
    <div>
        <div class="dash-cards">
            <div class="dash-card grad-blue">
                <div class="icon-badge"><i class="fa-solid fa-chart-column"></i></div>
                <div class="title">Total Koperasi</div>
                <div class="value" data-stat-key="koperasi" data-count-to="{{ $stats['koperasi'] }}">0</div>
                <div class="label">Layanan aktif</div>
            </div>
            <div class="dash-card grad-darkblue">
                <div class="icon-badge"><i class="fa-solid fa-users"></i></div>
                <div class="title">Total Anggota</div>
                <div class="value" data-stat-key="anggota" data-count-to="{{ $stats['anggota'] }}">0</div>
                <div class="label">Terdaftar sistem</div>
            </div>
            <div class="dash-card grad-teal">
                <div class="icon-badge"><i class="fa-solid fa-store"></i></div>
                <div class="title">Total Merchant</div>
                <div class="value" data-stat-key="merchant" data-count-to="{{ $stats['merchant'] }}">0</div>
                <div class="label">Partner aktif</div>
            </div>
            <div class="dash-card grad-orange">
                <div class="icon-badge"><i class="fa-solid fa-truck"></i></div>
                <div class="title">Total Driver</div>
                <div class="value" data-stat-key="driver" data-count-to="{{ $stats['driver'] }}">0</div>
                <div class="label">Siap antar</div>
            </div>
            <div class="dash-card grad-purple">
                <div class="icon-badge"><i class="fa-solid fa-receipt"></i></div>
                <div class="title">Pesanan Hari Ini</div>
                <div class="value" data-stat-key="pesanan_hari_ini" data-count-to="{{ $stats['pesanan_hari_ini'] }}">0</div>
                <div class="label">Update realtime</div>
            </div>
            <div class="dash-card grad-red">
                <div class="icon-badge"><i class="fa-solid fa-credit-card"></i></div>
                <div class="title">Transaksi Hari Ini</div>
                <div class="value" data-stat-key="transaksi_hari_ini" data-count-to="{{ $stats['transaksi_hari_ini'] }}">0</div>
                <div class="label">Gateway payment</div>
            </div>
        </div>
        <div class="space-y-6" style="margin-top:20px;">
            <div class="dash-panel">
                <div class="panel-title">Tren Transaksi Mingguan</div>
                <div data-chart-skeleton class="skeleton skeleton-chart"></div>
                <canvas id="dashboardChart" height="120"></canvas>
                <div class="muted">Data dummy untuk visualisasi.</div>
            </div>
        </div>
    </div>
    <div>
        <div class="dash-panel">
            <div class="panel-title">Transaksi Terbaru</div>
            <table class="dash-table">
                <thead>
                    <tr><th>Tanggal</th><th>Nomor</th><th>Jumlah</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <tr><td>Hari ini</td><td>#TRX-001</td><td>Rp 50.000</td><td>Berhasil</td></tr>
                    <tr><td>Hari ini</td><td>#TRX-002</td><td>Rp 120.000</td><td>Berhasil</td></tr>
                    <tr><td>Kemarin</td><td>#TRX-003</td><td>Rp 75.000</td><td>Gagal</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
