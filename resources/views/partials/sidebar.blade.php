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

        <button type="button" class="group-toggle" data-group="masterdata"><i class="fa-solid fa-database"></i><span>Masterdata</span><i class="fa-solid fa-chevron-down caret"></i></button>
        <div class="group-menu" data-group="masterdata">
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('koperasi.manage')))
            <a class="{{ $isActive(['koperasi.*']) }}" href="{{ route('koperasi.index') }}"><i class="fa-solid fa-building-columns"></i><span>Koperasi</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('anggota.manage')))
            <a class="{{ $isActive(['anggota.*']) }}" href="{{ route('anggota.index') }}"><i class="fa-solid fa-users"></i><span>Anggota</span></a>
            @endif
        </div>

        <button type="button" class="group-toggle" data-group="merchant"><i class="fa-solid fa-store"></i><span>Merchant</span><i class="fa-solid fa-chevron-down caret"></i></button>
        <div class="group-menu" data-group="merchant">
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('merchant.manage')))
            <a class="{{ $isActive(['merchant.*']) }}" href="{{ route('merchant.index') }}"><i class="fa-solid fa-store"></i><span>Merchant</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('produk.manage')))
            <a class="{{ $isActive(['produk.*']) }}" href="{{ route('produk.index') }}"><i class="fa-solid fa-burger"></i><span>Produk Makanan</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('kategori.manage')))
            <a class="{{ $isActive(['kategori-produk.*']) }}" href="{{ route('kategori-produk.index') }}"><i class="fa-solid fa-tags"></i><span>Kategori Produk</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('pesanan_makanan.view')))
            <a class="{{ $isActive(['pesanan-makanan.*']) }}" href="{{ route('pesanan-makanan.index') }}"><i class="fa-solid fa-receipt"></i><span>Pesanan Makanan</span></a>
            @endif
        </div>

        <button type="button" class="group-toggle" data-group="driver"><i class="fa-solid fa-motorcycle"></i><span>Driver</span><i class="fa-solid fa-chevron-down caret"></i></button>
        <div class="group-menu" data-group="driver">
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('driver.manage')))
            <a class="{{ $isActive(['driver.*']) }}" href="{{ route('driver.index') }}"><i class="fa-solid fa-id-badge"></i><span>Driver</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('driver.monitoring')))
            <a class="{{ $isActive(['driver.monitoring']) }}" href="{{ route('driver.monitoring') }}"><i class="fa-solid fa-location-dot"></i><span>Monitoring Driver</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('pesanan_ojek.manage')))
            <a class="{{ $isActive(['pesanan-ojek.*']) }}" href="{{ route('pesanan-ojek.index') }}"><i class="fa-solid fa-route"></i><span>Pesanan Ojek</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('tarif_kojek.manage')))
            <a class="{{ $isActive(['tarif-kojek.*']) }}" href="{{ route('tarif-kojek.index') }}"><i class="fa-solid fa-meter"></i><span>Tarif Kojek</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('tarif_delivery.manage')))
            <a class="{{ $isActive(['tarif-delivery-toko.*']) }}" href="{{ route('tarif-delivery-toko.index') }}"><i class="fa-solid fa-truck-fast"></i><span>Tarif Delivery</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('pesanan_makanan.view')))
            <a class="{{ $isActive(['pesanan-makanan.delivery-board']) }}" href="{{ route('pesanan-makanan.delivery-board') }}"><i class="fa-solid fa-box"></i><span>Pesanan Delivery</span></a>
            @endif
        </div>

        <button type="button" class="group-toggle" data-group="approval"><i class="fa-solid fa-user-check"></i><span>Approval</span><i class="fa-solid fa-chevron-down caret"></i></button>
        <div class="group-menu" data-group="approval">
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('approval.anggota')))
            <a class="{{ $isActive(['approval-anggota.index']) }}" href="{{ route('approval-anggota.index') }}"><i class="fa-solid fa-id-card"></i><span>Approval Anggota</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('approval.merchant')))
            <a class="{{ $isActive(['approval-merchant.index']) }}" href="{{ route('approval-merchant.index') }}"><i class="fa-solid fa-store-circle-check"></i><span>Approval Seller</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('approval.driver')))
            <a class="{{ $isActive(['approval-driver.index']) }}" href="{{ route('approval-driver.index') }}"><i class="fa-solid fa-id-badge"></i><span>Approval Driver</span></a>
            @endif
        </div>

        <button type="button" class="group-toggle" data-group="setting"><i class="fa-solid fa-gear"></i><span>Setting</span><i class="fa-solid fa-chevron-down caret"></i></button>
        <div class="group-menu" data-group="setting">
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('gateway.setup')))
            <a class="{{ $isActive(['setup-gateway.*']) }}" href="{{ route('setup-gateway.index') }}"><i class="fa-solid fa-plug"></i><span>Payment Gateway</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('users.manage')))
            <a class="{{ $isActive(['access-roles.*']) }}" href="{{ route('access-roles.index') }}"><i class="fa-solid fa-user-shield"></i><span>Hak Akses (Role)</span></a>
            <a class="{{ $isActive(['permissions.index']) }}" href="{{ route('permissions.index') }}"><i class="fa-solid fa-key"></i><span>Permissions</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('gateway.logs')))
            <a class="{{ $isActive(['gateway-logs.*']) }}" href="{{ route('gateway-logs.index') }}"><i class="fa-solid fa-database"></i><span>Gateway Logs</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('gateway.transactions')))
            <a class="{{ $isActive(['transaksi-gateway.*']) }}" href="#"><i class="fa-solid fa-file-invoice-dollar"></i><span>Transaksi Gateway</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('wallet.view')))
            <a class="{{ $isActive(['dompet.*']) }}" href="#"><i class="fa-solid fa-wallet"></i><span>Dompet</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('wallet.transactions')))
            <a class="{{ $isActive(['transaksi-dompet.*']) }}" href="#"><i class="fa-solid fa-money-bill-transfer"></i><span>Transaksi Dompet</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('users.manage')))
            <a class="{{ $isActive(['users.*']) }}" href="{{ route('users.index') }}"><i class="fa-solid fa-user-gear"></i><span>Pengguna</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('pengaturan.view')))
            <a class="{{ $isActive(['pengaturan']) }}" href="#"><i class="fa-solid fa-gear"></i><span>Pengaturan</span></a>
            @endif
            @if(auth()->check() && (auth()->user()->hasRole('superadmin') || auth()->user()->can('laporan.view')))
            <a class="{{ $isActive(['laporan']) }}" href="#"><i class="fa-solid fa-chart-line"></i><span>Laporan</span></a>
            @endif
        </div>
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
