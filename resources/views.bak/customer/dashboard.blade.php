@extends('layouts.app')

@section('title', 'Beranda Customer')
@section('breadcrumb', 'Beranda Customer')

@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Area pelanggan</div><h1>Beranda</h1><p>Temukan produk favorit dan pantau hubungan layananmu dengan RotiKita.</p></div>
    </div>
    <div class="welcome">
        <div><div class="eyebrow">Senang melihatmu lagi</div><h2>Halo, {{ auth()->user()->name }} 👋</h2><p>Akun pelangganmu sudah aktif. Katalog dan pemesanan akan tersedia setelah modul berikutnya selesai dikembangkan.</p></div>
        <div class="welcome-icon" aria-hidden="true">🍞</div>
    </div>
    <div class="quick-grid">
        <div class="quick"><div class="quick-icon">▤</div><strong>Katalog produk</strong><p>Jelajahi roti dan produk favorit saat modul katalog tersedia.</p></div>
        <div class="quick"><div class="quick-icon">▣</div><strong>Pesanan saya</strong><p>Riwayat dan status pesanan akan ditampilkan di bagian ini.</p></div>
        <div class="quick"><div class="quick-icon">✉</div><strong>Kritik & saran</strong><p>Sampaikan pengalaman dan masukan untuk membantu kami meningkatkan layanan.</p></div>
    </div>
@endsection
