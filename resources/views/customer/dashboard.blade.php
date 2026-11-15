@extends('layouts.app')

@section('title', 'Beranda Customer')
@section('breadcrumb', 'Beranda')

@section('content')
@php
    $customerOrderSteps = [
        'pending' => 'Pesanan dibuat',
        'confirmed' => 'Dikonfirmasi',
        'preparing' => 'Sedang disiapkan',
        'ready' => 'Siap diambil',
        'completed' => 'Selesai',
    ];
    $customerStepKeys = array_keys($customerOrderSteps);
    $customerCurrentStep = $activeOrder ? array_search($activeOrder->status, $customerStepKeys, true) : false;
    $customerStepLabels = [
        'pending' => 'Menunggu',
        'confirmed' => 'Dikonfirmasi',
        'preparing' => 'Sedang diproses',
        'ready' => 'Siap',
        'completed' => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];
    $customerPaymentLabels = ['pending' => 'Menunggu pembayaran', 'awaiting_verification' => 'Menunggu verifikasi', 'paid' => 'Lunas', 'refunded' => 'Dikembalikan', 'rejected' => 'Ditolak', 'failed' => 'Gagal'];
@endphp

<div class="rk-customer-dashboard">
    <section class="rk-customer-welcome-panel">
        <div class="rk-customer-welcome-copy">
            <span class="rk-rebuild-eyebrow">Ruang hangat untuk pelanggan</span>
            <h1>Selamat datang, {{ auth()->user()->name }}<span>.</span></h1>
            <p>Pesanan, kabar terbaru, dan roti favoritmu—semuanya ada di satu tempat.</p>
            <a class="rk-rebuild-button" href="{{ route('storefront.menu') }}">Jelajahi menu <span aria-hidden="true">↗</span></a>
        </div>
        <div class="rk-customer-welcome-art" aria-hidden="true"><span>ROTI<br>HANGAT</span><i></i></div>
    </section>

    <section class="rk-customer-stat-row" aria-label="Ringkasan akun">
        <article><span>Total pesanan</span><strong>{{ $totalOrders }}</strong><small>Sepanjang waktu</small></article>
        <article><span>Pesanan berjalan</span><strong>{{ $activeOrders }}</strong><small>Dalam proses</small></article>
        <article><span>Masukan terkirim</span><strong>{{ $feedbackCount }}</strong><small>Suaramu berarti</small></article>
    </section>

    <div class="rk-customer-dashboard-grid">
        <div class="rk-customer-main-column">
            <section class="rk-rebuild-panel rk-customer-active-panel">
                <div class="rk-rebuild-panel-heading">
                    <div><span class="rk-rebuild-eyebrow">Pantau pesanan</span><h2>Pesanan aktif</h2></div>
                    @if ($activeOrder)
                        <a class="rk-rebuild-text-link" href="{{ route('customer.orders.show', $activeOrder) }}">Detail pesanan <span aria-hidden="true">→</span></a>
                    @endif
                </div>
                @if ($activeOrder)
                    <div class="rk-customer-order-summary">
                        <div class="rk-customer-order-number"><span>No. pesanan</span><strong>{{ $activeOrder->order_number }}</strong><small>{{ $activeOrder->created_at->format('d M Y · H:i') }}</small></div>
                        <span class="rk-rebuild-status rk-status-{{ $activeOrder->status }}">{{ $customerStepLabels[$activeOrder->status] ?? ucfirst(str_replace('_', ' ', $activeOrder->status)) }}</span>
                        <strong class="rk-customer-order-amount">Rp {{ number_format((float) $activeOrder->total_amount, 0, ',', '.') }}</strong>
                    </div>
                    <div class="rk-customer-order-items">
                        @foreach ($activeOrder->items as $item)
                            <span>{{ $item->item_name_snapshot }} <b>× {{ $item->quantity }}</b></span>
                        @endforeach
                    </div>
                    <ol class="rk-customer-timeline" aria-label="Progres pesanan">
                        @foreach ($customerOrderSteps as $status => $label)
                            @php
                                $stepIndex = array_search($status, $customerStepKeys, true);
                                $statusHistory = $activeOrder->statusHistories->firstWhere('status', $status);
                                $stepComplete = $customerCurrentStep !== false && $stepIndex < $customerCurrentStep;
                                $stepCurrent = $activeOrder->status === $status;
                            @endphp
                            <li class="{{ $stepComplete ? 'is-complete' : '' }} {{ $stepCurrent ? 'is-current' : '' }}">
                                <span class="rk-customer-timeline-marker">{{ $stepComplete ? '✓' : $stepIndex + 1 }}</span>
                                <strong>{{ $label }}</strong>
                                @if ($statusHistory?->created_at)
                                    <small>{{ $statusHistory->created_at->format('d M · H:i') }}</small>
                                @elseif ($status === 'pending')
                                    <small>{{ $activeOrder->created_at->format('d M · H:i') }}</small>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="rk-customer-no-active"><span class="rk-customer-empty-mark" aria-hidden="true">✳</span><div><strong>Belum ada pesanan yang berjalan</strong><p>Pesanan berikutnya akan tampil di sini beserta progresnya.</p></div><a class="rk-rebuild-button" href="{{ route('storefront.menu') }}">Pilih roti</a></div>
                @endif
            </section>

            <section class="rk-rebuild-panel rk-customer-recent-panel">
                <div class="rk-rebuild-panel-heading"><div><span class="rk-rebuild-eyebrow">Catatan perjalanan</span><h2>Pesanan terakhir</h2></div><a class="rk-rebuild-text-link" href="{{ route('customer.orders') }}">Semua pesanan <span aria-hidden="true">→</span></a></div>
                <div class="rk-customer-recent-list">
                    @forelse ($recentOrders as $order)
                        <a class="rk-customer-recent-order" href="{{ route('customer.orders.show', $order) }}">
                            <span class="rk-customer-recent-icon" aria-hidden="true">R</span>
                            <span class="rk-customer-recent-meta"><strong>{{ $order->order_number }}</strong><small>{{ $order->created_at->format('d M Y · H:i') }} · {{ $order->items->count() }} item</small></span>
                            <span class="rk-customer-recent-status"><span class="rk-rebuild-status rk-status-{{ $order->status }}">{{ $customerStepLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status)) }}</span><small>{{ $customerPaymentLabels[$order->payment?->status ?? ''] ?? 'Pembayaran belum tersedia' }}</small></span>
                            <strong class="rk-customer-recent-total">Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</strong>
                        </a>
                    @empty
                        <div class="rk-customer-recent-empty">Belum ada riwayat. Mulai dengan memilih roti favoritmu.</div>
                    @endforelse
                </div>
            </section>

            <section class="rk-customer-favorites">
                <div class="rk-rebuild-panel-heading"><div><span class="rk-rebuild-eyebrow">Dari etalase kami</span><h2>Temukan favoritmu</h2></div><a class="rk-rebuild-text-link" href="{{ route('customer.catalog') }}">Lihat katalog <span aria-hidden="true">→</span></a></div>
                <div class="rk-customer-product-grid">
                    @forelse ($featuredProducts as $product)
                        <a class="rk-customer-product-card" href="{{ route('storefront.product', $product) }}">
                            <span class="rk-customer-product-image">
                                @if ($product->displayImagePath())
                                    <img src="{{ asset('storage/'.$product->displayImagePath()) }}" alt="{{ $product->name }}" loading="lazy">
                                @else
                                    <span class="rk-customer-product-placeholder" aria-hidden="true">R</span>
                                @endif
                            </span>
                            <span class="rk-customer-product-copy"><small>{{ $product->category }}</small><strong>{{ $product->name }}</strong><b>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</b></span>
                        </a>
                    @empty
                        <div class="rk-customer-recent-empty">Produk pilihan akan tampil di sini saat tersedia.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="rk-customer-side-column">
            <section class="rk-customer-bakery-banner">
                <span class="rk-rebuild-eyebrow">Dari dapur RotiKita</span>
                <div class="rk-customer-banner-bread" aria-hidden="true">✳</div>
                <h2>Hari terasa lebih baik dengan roti yang hangat.</h2>
                <p>Temukan pilihan bakery yang dibuat untuk menemani momenmu.</p>
                <a href="{{ route('storefront.menu') }}">Lihat menu <span aria-hidden="true">↗</span></a>
            </section>
            <section class="rk-customer-shortcuts rk-rebuild-panel">
                <span class="rk-rebuild-eyebrow">Butuh bantuan?</span><h2>Kami siap membantu.</h2>
                <a href="{{ route('customer.tickets.index') }}">Pusat bantuan <span aria-hidden="true">→</span></a>
                <a href="{{ route('customer.feedback') }}">Kirim kritik &amp; saran <span aria-hidden="true">→</span></a>
            </section>
            <section class="rk-customer-feedback-note"><span class="rk-customer-feedback-mark" aria-hidden="true">“</span><p>Setiap masukan membantu kami menyajikan pengalaman yang lebih baik.</p><a href="{{ route('customer.feedback') }}">Bagikan pengalamanmu</a></section>
        </aside>
    </div>
</div>
@endsection
