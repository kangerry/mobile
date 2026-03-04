<aside class="sidebar">
    <div class="brand">
        <i class="fa-solid fa-cubes"></i> KOMERA Backoffice
    </div>
    <?php
    $isActive = function($patterns) {
        foreach ((array)$patterns as $p) {
            if (request()->routeIs($p)) return 'active';
        }
        return '';
    };
    ?>
    <nav class="menu">
        <a class="{{ $isActive(['dashboard']) }}" href="{{ route('dashboard') }}"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
        <a class="{{ $isActive(['koperasi.*']) }}" href="{{ route('koperasi.index') }}"><i class="fa-solid fa-building-columns"></i><span>Koperasi</span></a>
        <a class="{{ $isActive(['anggota.*']) }}" href="{{ route('anggota.index') }}"><i class="fa-solid fa-users"></i><span>Anggota</span></a>
        <a class="{{ $isActive(['merchant.*']) }}" href="{{ route('merchant.index') }}"><i class="fa-solid fa-store"></i><span>Merchant</span></a>
        <a class="{{ $isActive(['produk.*']) }}" href="{{ route('produk.index') }}"><i class="fa-solid fa-burger"></i><span>Produk Makanan</span></a>
        <a class="{{ $isActive(['kategori-produk.*']) }}" href="{{ route('kategori-produk.index') }}"><i class="fa-solid fa-tags"></i><span>Kategori Produk</span></a>
        <a class="{{ $isActive(['pesanan-makanan.*']) }}" href="{{ route('pesanan-makanan.index') }}"><i class="fa-solid fa-receipt"></i><span>Pesanan Makanan</span></a>
        <a class="{{ $isActive(['driver.*']) }}" href="{{ route('driver.index') }}"><i class="fa-solid fa-motorcycle"></i><span>Driver</span></a>
        <a class="{{ $isActive(['pesanan-ojek.*']) }}" href="{{ route('pesanan-ojek.index') }}"><i class="fa-solid fa-route"></i><span>Pesanan Ojek</span></a>
        <a class="{{ $isActive(['dompet.*']) }}" href="#"><i class="fa-solid fa-wallet"></i><span>Dompet</span></a>
        <a class="{{ $isActive(['transaksi-dompet.*']) }}" href="#"><i class="fa-solid fa-money-bill-transfer"></i><span>Transaksi Dompet</span></a>
        <a class="{{ $isActive(['setup-gateway.*']) }}" href="{{ route('setup-gateway.index') }}"><i class="fa-solid fa-plug"></i><span>Payment Gateway</span></a>
        <a class="{{ $isActive(['tarif-kojek.*']) }}" href="{{ route('tarif-kojek.index') }}"><i class="fa-solid fa-meter"></i><span>Tarif Kojek</span></a>
        <a class="{{ $isActive(['tarif-delivery-toko.*']) }}" href="{{ route('tarif-delivery-toko.index') }}"><i class="fa-solid fa-truck-fast"></i><span>Tarif Delivery</span></a>
        <a class="{{ $isActive(['approval-anggota.index']) }}" href="{{ route('approval-anggota.index') }}"><i class="fa-solid fa-user-check"></i><span>Approval Anggota</span></a>
        <a class="{{ $isActive(['approval-merchant.index']) }}" href="{{ route('approval-merchant.index') }}"><i class="fa-solid fa-store-circle-check"></i><span>Approval Seller</span></a>
        <a class="{{ $isActive(['approval-driver.index']) }}" href="{{ route('approval-driver.index') }}"><i class="fa-solid fa-id-badge"></i><span>Approval Driver</span></a>
        <a class="{{ $isActive(['transaksi-gateway.*']) }}" href="#"><i class="fa-solid fa-file-invoice-dollar"></i><span>Transaksi Gateway</span></a>
        <a class="{{ $isActive(['laporan']) }}" href="#"><i class="fa-solid fa-chart-line"></i><span>Laporan</span></a>
        <a class="{{ $isActive(['pengaturan']) }}" href="#"><i class="fa-solid fa-gear"></i><span>Pengaturan</span></a>
    </nav>
    <div class="mode-switch">
        <div class="title">Mode Tampilan</div>
        <div class="group">
            <button type="button" class="btn" data-ui-mode="auto">Auto</button>
            <button type="button" class="btn" data-ui-mode="mobile">Mobile</button>
            <button type="button" class="btn" data-ui-mode="desktop">Desktop</button>
        </div>
    </div>
</aside>
