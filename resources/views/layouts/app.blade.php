<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>document.documentElement.classList.add('rk-js');</script>
    @php
        $pageTitle = trim($__env->yieldContent('title', 'Dashboard'));
    @endphp
    <title>{{ $pageTitle }} — RotiKita Bakery</title>
    @include('partials.brand-icons')
    <link rel="stylesheet" href="{{ asset('css/rotikita.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rotikita-v2-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rotikita-reconstruction.css') }}?v={{ filemtime(public_path('css/rotikita-reconstruction.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/page-transitions.css') }}?v={{ filemtime(public_path('css/page-transitions.css')) }}">
    <script>try{const k='rotikita:page-navigation',t=Number(sessionStorage.getItem(k));if(t>0&&Date.now()-t<15000&&performance.getEntriesByType('navigation')[0]?.type!=='back_forward'){document.documentElement.classList.add('rks-navigation-pending');setTimeout(()=>{document.documentElement.classList.remove('rks-navigation-pending');sessionStorage.removeItem(k)},15000)}else sessionStorage.removeItem(k)}catch{}</script>
</head>
<body>
@include('partials.page-loader')
@php
    $currentUser = auth()->user();
    $isAdmin = $currentUser && $currentUser->role === 'admin';
    $adminInboxUnread = $isAdmin ? $currentUser->unreadNotifications()->whereIn('type', [\App\Notifications\PaymentProofSubmitted::class, \App\Notifications\OrderCreated::class, \App\Notifications\PaymentStatusChanged::class])->count() : 0;
    $customerPaymentUnread = ! $isAdmin ? $currentUser->unreadNotifications()->where('type', \App\Notifications\PaymentStatusChanged::class)->count() : 0;
    $adminNavigation = [
        ['label' => 'Artikel', 'icon' => 'A', 'route' => 'admin.articles.index', 'active' => 'admin.articles.*'],
        ['label' => 'Dashboard', 'icon' => '▦', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard'],
        ['label' => 'Pelanggan', 'icon' => '♙', 'route' => 'admin.customers', 'active' => 'admin.customers'],
        ['label' => 'Produk', 'icon' => '▤', 'route' => 'admin.products', 'active' => 'admin.products'],
        ['label' => 'Combo', 'icon' => 'C', 'route' => 'admin.combos.index', 'active' => 'admin.combos.*'],
        ['label' => 'Pemesanan', 'icon' => '▣', 'route' => 'admin.orders', 'active' => 'admin.orders'],
        ['label' => 'Inbox', 'icon' => 'N', 'route' => 'admin.inbox', 'active' => 'admin.inbox'],
        ['label' => 'Promosi', 'icon' => '✳', 'route' => 'admin.promotions', 'active' => 'admin.promotions'],
        ['label' => 'Pengaturan pembayaran', 'icon' => 'P', 'route' => 'admin.payment-settings.edit', 'active' => 'admin.payment-settings.*'],
        ['label' => 'Feedback', 'icon' => '✉', 'route' => 'admin.feedback', 'active' => 'admin.feedback'],
        ['label' => 'Laporan pendapatan', 'icon' => 'Rp', 'route' => 'admin.reports.revenue', 'active' => 'admin.reports.revenue*'],
    ];
    $customerNavigation = [
        ['label' => 'Beranda', 'icon' => '⌂', 'route' => 'customer.dashboard', 'active' => 'customer.dashboard'],
        ['label' => 'Katalog', 'icon' => '▤', 'route' => 'customer.catalog', 'active' => 'customer.catalog'],
        ['label' => 'Keranjang', 'icon' => 'C', 'route' => 'customer.cart', 'active' => 'customer.cart*'],
        ['label' => 'Pesanan saya', 'icon' => '▣', 'route' => 'customer.orders', 'active' => 'customer.orders'],
        ['label' => 'Kritik & saran', 'icon' => '✉', 'route' => 'customer.feedback', 'active' => 'customer.feedback'],
    ];
    $adminNavigation[] = ['label' => 'Tiket bantuan', 'icon' => 'T', 'route' => 'admin.tickets.index', 'active' => 'admin.tickets.*'];
    $adminNavigationGroups = [
        ['label' => 'Ringkasan', 'icon' => '▦', 'items' => [['label' => 'Dashboard', 'icon' => '⌂', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard']]],
        ['label' => 'Operasional', 'icon' => '◷', 'items' => [['label' => 'Pesanan', 'icon' => '▤', 'route' => 'admin.orders', 'active' => 'admin.orders']]],
        ['label' => 'Katalog Produk', 'icon' => '◈', 'items' => [
            ['label' => 'Produk', 'icon' => '◤', 'route' => 'admin.products', 'active' => 'admin.products'],
            ['label' => 'Paket Combo', 'icon' => '▧', 'route' => 'admin.combos.index', 'active' => 'admin.combos.*'],
        ]],
        ['label' => 'Customer Relationship & Layanan', 'icon' => '♧', 'items' => [
            ['label' => 'Pelanggan', 'icon' => '♙', 'route' => 'admin.customers', 'active' => 'admin.customers'],
            ['label' => 'Inbox', 'icon' => '✉', 'route' => 'admin.inbox', 'active' => 'admin.inbox'],
            ['label' => 'Feedback', 'icon' => '♡', 'route' => 'admin.feedback', 'active' => 'admin.feedback'],
            ['label' => 'Tiket Bantuan', 'icon' => '?', 'route' => 'admin.tickets.index', 'active' => 'admin.tickets.*'],
        ]],
        ['label' => 'Pemasaran', 'icon' => '✳', 'items' => [
            ['label' => 'Promosi', 'icon' => '%', 'route' => 'admin.promotions', 'active' => 'admin.promotions'],
            ['label' => 'Artikel', 'icon' => 'A', 'route' => 'admin.articles.index', 'active' => 'admin.articles.*'],
        ]],
        ['label' => 'Analisis Bisnis', 'icon' => '↗', 'items' => [['label' => 'Laporan Pendapatan', 'icon' => 'Rp', 'route' => 'admin.reports.revenue', 'active' => 'admin.reports.revenue*']]],
        ['label' => 'Pengaturan', 'icon' => '⚙', 'items' => [['label' => 'Pengaturan Pembayaran', 'icon' => 'P', 'route' => 'admin.payment-settings.edit', 'active' => 'admin.payment-settings.*']]],
    ];
    $customerNavigation[] = ['label' => 'Pusat bantuan', 'icon' => 'T', 'route' => 'customer.tickets.index', 'active' => 'customer.tickets.*'];
    $navigation = $isAdmin ? $adminNavigation : $customerNavigation;
@endphp
<div class="rk-app {{ $isAdmin ? 'rk-app-admin' : '' }}">
    <aside class="rk-sidebar" id="rk-sidebar">
        <a class="rk-brand" href="{{ route('home') }}">
            <span class="rk-brand-mark">R</span>
            <span>RotiKita<span class="rk-brand-dot">.</span></span>
        </a>

        <div class="rk-nav-caption">{{ $isAdmin ? 'Workspace admin' : 'Area pelanggan' }}</div>
        @if ($isAdmin)
            <nav class="rk-nav rk-admin-nav" aria-label="Navigasi administrasi">
                @foreach ($adminNavigationGroups as $group)
                    @php($currentRouteName = request()->route()?->getName() ?? '')
                    @php($groupIsActive = collect($group['items'])->contains(fn ($item) => request()->routeIs($item['active']) || str_starts_with($currentRouteName, rtrim($item['active'], '*'))))
                    <section class="rk-admin-nav-group {{ $groupIsActive ? 'is-current' : '' }}" data-admin-nav-group>
                        <button class="rk-admin-nav-group-toggle" type="button" data-admin-nav-toggle aria-expanded="{{ $groupIsActive ? 'true' : 'false' }}">
                            <span class="rk-nav-icon" aria-hidden="true">{{ $group['icon'] }}</span>
                            <span class="rk-nav-label">{{ $group['label'] }}</span>
                            <span class="rk-admin-nav-chevron" aria-hidden="true">⌄</span>
                        </button>
                        <div class="rk-admin-nav-items" @if (! $groupIsActive) hidden @endif>
                            @foreach ($group['items'] as $item)
                                <a class="rk-nav-link {{ request()->routeIs($item['active']) || str_starts_with($currentRouteName, rtrim($item['active'], '*')) ? 'is-active' : '' }}" href="{{ route($item['route']) }}" @if (request()->routeIs($item['active']) || str_starts_with($currentRouteName, rtrim($item['active'], '*'))) aria-current="page" @endif>
                                    <span class="rk-nav-icon" aria-hidden="true">{{ $item['icon'] }}</span>
                                    <span class="rk-nav-label">{{ $item['label'] }}@if($item['route'] === 'admin.inbox' && $adminInboxUnread > 0) ({{ $adminInboxUnread }})@endif</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </nav>
            <div class="rk-sidebar-bottom rk-admin-sidebar-footer">
                <a class="rk-admin-home-link" href="{{ route('home') }}" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">↗</span><span>Kembali ke Beranda</span></a>
                <div class="rk-admin-sidebar-profile"><span class="rk-avatar" aria-hidden="true">{{ strtoupper(substr($currentUser?->name ?? 'A', 0, 1)) }}</span><span><strong>{{ $currentUser?->name }}</strong><small>Administrator</small></span></div>
                <form class="rk-admin-sidebar-logout" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><span aria-hidden="true">↪</span><span>Keluar dari akun</span></button>
                </form>
            </div>
        @else
            <nav class="rk-nav" aria-label="Navigasi utama">
                @foreach ($navigation as $item)
                    <a class="rk-nav-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}" href="{{ route($item['route']) }}" @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                        <span class="rk-nav-icon" aria-hidden="true">{{ $item['icon'] }}</span>
                        <span class="rk-nav-label">{{ $item['label'] }}@if($item['route'] === 'customer.cart') ({{ array_sum(array_map('intval', session('rotikita_cart', []))) }})@elseif($item['route'] === 'customer.orders' && $customerPaymentUnread > 0) ({{ $customerPaymentUnread }})@endif</span>
                    </a>
                @endforeach
            </nav>
            <div class="rk-sidebar-bottom"><strong>RotiKita E-CRM</strong><br>Kelola hubungan, bukan hanya transaksi.<span class="rk-sidebar-note">Versi pengembangan · {{ date('Y') }}</span></div>
        @endif
    </aside>
    <button class="rk-sidebar-scrim" type="button" data-sidebar-close aria-label="Tutup navigasi"></button>

    <div class="rk-main">
        <header class="rk-topbar">
            <div class="rk-topbar-leading">
                <button class="rk-sidebar-toggle" type="button" data-sidebar-toggle aria-expanded="false" aria-controls="rk-sidebar" aria-label="Buka navigasi">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <div class="rk-breadcrumb">RotiKita <span aria-hidden="true">/</span> <strong>@yield('breadcrumb', 'Dashboard')</strong></div>
            </div>
            <div class="rk-user-menu">
                @if (! $isAdmin)
                    <a class="rk-customer-notification" href="{{ route('customer.orders') }}" aria-label="Notifikasi pesanan{{ $customerPaymentUnread > 0 ? ', '.$customerPaymentUnread.' belum dibaca' : '' }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" /></svg>
                        @if ($customerPaymentUnread > 0)<span>{{ $customerPaymentUnread }}</span>@endif
                    </a>
                @endif
                <div class="rk-avatar" aria-hidden="true">{{ strtoupper(substr($currentUser?->name ?? 'U', 0, 1)) }}</div>
                <div class="rk-user-meta">
                    <div class="rk-user-name">{{ $currentUser?->name }}</div>
                    <div class="rk-user-role">{{ $isAdmin ? 'Administrator' : 'Customer' }}</div>
                </div>
                @unless ($isAdmin)
                    <form class="rk-logout-form" method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="rk-btn rk-btn-quiet rk-btn-small" type="submit">Keluar <span aria-hidden="true">↗</span></button>
                    </form>
                @endunless
            </div>
        </header>

        <main class="rk-content">
            @if (session('success'))
                <div class="rk-alert rk-alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rk-alert rk-alert-error" role="alert">{{ session('error') }}</div>
            @endif
            @yield('content')
            <footer class="rk-footer">© {{ date('Y') }} RotiKita · Sistem Informasi Customer Relationship Management</footer>
            @include('components.group-identity')
        </main>
    </div>
</div>
    <script defer src="{{ asset('js/rotikita-v2-ui.js') }}"></script>
    <script defer src="{{ asset('js/rotikita-storefront.js') }}?v={{ filemtime(public_path('js/rotikita-storefront.js')) }}"></script>
    <script defer src="{{ asset('js/rotikita-cart.js') }}?v={{ filemtime(public_path('js/rotikita-cart.js')) }}"></script>
    <script defer src="{{ asset('js/rotikita-page-loader.js') }}?v={{ filemtime(public_path('js/rotikita-page-loader.js')) }}"></script>
</body>
</html>
