@extends('layouts.app')
@section('title', 'Beranda Customer')
@section('breadcrumb', 'Beranda')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Area pelanggan</p><h1>Beranda</h1><p>Temukan roti favoritmu, cek pesanan, dan tetap terhubung dengan RotiKita.</p></div><span class="rk-status rk-status-live">Akun aktif</span></div>
<section class="rk-welcome rk-welcome-v2 rk-customer-welcome"><div class="rk-welcome-copy"><p class="rk-eyebrow">Senang melihatmu di sini</p><h2>Halo, {{ auth()->user()->name }}</h2><p>Roti segar dan pengalaman belanja yang lebih nyaman ada di sini. Yuk, lanjutkan pesanan atau jelajahi menu favoritmu.</p><div class="rk-welcome-actions"><a class="rk-btn" href="{{ route('storefront.menu') }}">Jelajahi menu</a><a class="rk-btn rk-btn-quiet" href="{{ route('customer.orders') }}">Lihat pesanan</a></div></div><div class="rk-welcome-art rk-customer-art" aria-hidden="true">🥐<span>fresh<br>daily</span></div></section>
<section class="rk-stats rk-stats-three" aria-label="Ringkasan akun"><article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total pesanan</div><div class="rk-stat-value">{{ $totalOrders }}</div></article><article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Pesanan berjalan</div><div class="rk-stat-value">{{ $activeOrders }}</div></article><article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Feedback terkirim</div><div class="rk-stat-value">{{ $feedbackCount }}</div></article></section>
@if($feedbackCount===0)<p class="rk-helper-note">Belum ada feedback dari akun ini. <a class="rk-link" href="{{ route('customer.feedback') }}">Kirim masukan pertama</a>.</p>@endif
<div class="rk-section-heading"><div><p class="rk-eyebrow">Lanjutkan aktivitas</p><h2>Ada yang bisa kami bantu?</h2></div></div><div class="rk-quick-grid rk-quick-grid-v2"><a class="rk-quick-card" href="{{ route('storefront.menu') }}"><strong>Jelajahi menu</strong><p>Temukan roti, pastry, dan produk favorit.</p></a><a class="rk-quick-card" href="{{ route('customer.orders') }}"><strong>Pesanan saya</strong><p>Lihat status dan riwayat pesanan.</p></a><a class="rk-quick-card" href="{{ route('customer.feedback') }}"><strong>Kritik dan saran</strong><p>Ceritakan pengalamanmu ke tim kami.</p></a></div>
<section class="rk-panel rk-panel-v2 rk-mt-18"><div class="rk-panel-heading"><div><h2>Pesanan terakhir</h2><p>Riwayat pesanan akunmu.</p></div><a href="{{ route('customer.orders') }}" class="rk-link">Lihat semua</a></div>
@forelse($recentOrders as $order)
<a class="rk-customer-order-card" href="{{ route('customer.orders.show', $order) }}"><div class="rk-customer-order-copy"><strong>Pesanan #{{ $order->order_number }}</strong><small>{{ $order->created_at->format('d M Y · H:i') }}</small><span class="rk-badge rk-badge-{{ $order->status }}">{{ ucfirst(str_replace('_', ' ', $order->status)) }}</span></div><div class="rk-customer-order-total"><strong>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</strong><small>{{ $order->payment ? ucfirst(str_replace('_', ' ', $order->payment->status)) : 'Belum ada pembayaran' }}</small></div></a>
@empty
<div class="rk-empty-state"><strong>Belum ada pesanan</strong><p>Pesanan yang kamu buat akan muncul di sini.</p><a class="rk-btn" href="{{ route('storefront.menu') }}">Jelajahi menu</a></div>
@endforelse
</section>
@endsection
