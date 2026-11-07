<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $pageTitle = trim($__env->yieldContent('title', 'Dashboard'));
    @endphp
    <title>{{ $pageTitle }} — RotiKita Bakery</title>
    @include('partials.brand-icons')
    <link rel="stylesheet" href="{{ asset('css/rotikita.css') }}">
    <link rel="stylesheet" href="{{ asset('css/rotikita-v2-pages.css') }}">
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
        ['label' => 'Pembayaran', 'icon' => 'P', 'route' => 'admin.payment-settings.edit', 'active' => 'admin.payment-settings.*'],
        ['label' => 'Feedback', 'icon' => '✉', 'route' => 'admin.feedback', 'active' => 'admin.feedback'],
    ];
    $customerNavigation = [
        ['label' => 'Beranda', 'icon' => '⌂', 'route' => 'customer.dashboard', 'active' => 'customer.dashboard'],
        ['label' => 'Katalog', 'icon' => '▤', 'route' => 'customer.catalog', 'active' => 'customer.catalog'],
        ['label' => 'Keranjang', 'icon' => 'C', 'route' => 'customer.cart', 'active' => 'customer.cart*'],
        ['label' => 'Pesanan saya', 'icon' => '▣', 'route' => 'customer.orders', 'active' => 'customer.orders'],
        ['label' => 'Kritik & saran', 'icon' => '✉', 'route' => 'customer.feedback', 'active' => 'customer.feedback'],
    ];
    $adminNavigation[] = ['label' => 'Tiket bantuan', 'icon' => 'T', 'route' => 'admin.tickets.index', 'active' => 'admin.tickets.*'];
    $customerNavigation[] = ['label' => 'Pusat bantuan', 'icon' => 'T', 'route' => 'customer.tickets.index', 'active' => 'customer.tickets.*'];
    $navigation = $isAdmin ? $adminNavigation : $customerNavigation;
@endphp
<div class="rk-app">
    <aside class="rk-sidebar">
        <a class="rk-brand" href="{{ route('home') }}">
            <span class="rk-brand-mark">R</span>
            <span>RotiKita<span class="rk-brand-dot">.</span></span>
        </a>

        <div class="rk-nav-caption">{{ $isAdmin ? 'Workspace admin' : 'Area pelanggan' }}</div>
        <nav class="rk-nav" aria-label="Navigasi utama">
            @foreach ($navigation as $item)
                <a class="rk-nav-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}"
                   href="{{ route($item['route']) }}"
                   @if (request()->routeIs($item['active'])) aria-current="page" @endif>
                    <span class="rk-nav-icon" aria-hidden="true">{{ $item['icon'] }}</span>
                    <span class="rk-nav-label">{{ $item['label'] }}@if($item['route'] === 'admin.inbox' && $adminInboxUnread > 0) ({{ $adminInboxUnread }})@elseif($item['route'] === 'customer.cart') ({{ array_sum(array_map('intval', session('rotikita_cart', []))) }})@elseif($item['route'] === 'customer.orders' && $customerPaymentUnread > 0) ({{ $customerPaymentUnread }})@endif</span>
                </a>
            @endforeach
        </nav>

        <div class="rk-sidebar-bottom">
            <strong>RotiKita E-CRM</strong><br>
            Kelola hubungan, bukan hanya transaksi.
            <span class="rk-sidebar-note">Versi pengembangan · {{ date('Y') }}</span>
        </div>
    </aside>

    <div class="rk-main">
        <header class="rk-topbar">
            <div class="rk-breadcrumb">RotiKita <span aria-hidden="true">/</span> <strong>@yield('breadcrumb', 'Dashboard')</strong></div>
            <div class="rk-user-menu">
                <div class="rk-avatar" aria-hidden="true">{{ strtoupper(substr($currentUser?->name ?? 'U', 0, 1)) }}</div>
                <div class="rk-user-meta">
                    <div class="rk-user-name">{{ $currentUser?->name }}</div>
                    <div class="rk-user-role">{{ $isAdmin ? 'Administrator' : 'Customer' }}</div>
                </div>
                <form class="rk-logout-form" method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rk-btn rk-btn-quiet rk-btn-small" type="submit">Keluar <span aria-hidden="true">↗</span></button>
                </form>
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
        </main>
    </div>
</div>
    <script defer src="{{ asset('js/rotikita-v2-ui.js') }}"></script>
    <script defer src="{{ asset('js/rotikita-storefront.js') }}?v={{ filemtime(public_path('js/rotikita-storefront.js')) }}"></script>
    <script defer src="{{ asset('js/rotikita-cart.js') }}?v={{ filemtime(public_path('js/rotikita-cart.js')) }}"></script>
    <script defer src="{{ asset('js/rotikita-page-loader.js') }}?v={{ filemtime(public_path('js/rotikita-page-loader.js')) }}"></script>
</body>
</html>
