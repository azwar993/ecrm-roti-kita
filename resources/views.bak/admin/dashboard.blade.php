@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('breadcrumb', 'Dashboard Admin')

@section('content')
    <div class="page-heading">
        <div><div class="eyebrow">Ruang kerja admin</div><h1>Dashboard</h1><p>Ringkasan pengelolaan hubungan pelanggan RotiKita.</p></div>
    </div>
    <div class="welcome">
        <div><div class="eyebrow">Selamat datang kembali</div><h2>Halo, {{ auth()->user()->name }} 👋</h2><p>Fondasi admin sudah aktif. Selanjutnya modul pelanggan, produk, pemesanan, promosi, dan feedback akan dihubungkan ke database.</p></div>
        <div class="welcome-icon" aria-hidden="true">🥐</div>
    </div>
    <div class="grid">
        <div class="card"><div class="stat-label">Total pelanggan</div><div class="stat-value">—</div><div class="stat-caption">Menunggu modul pelanggan</div></div>
        <div class="card"><div class="stat-label">Pesanan masuk</div><div class="stat-value">—</div><div class="stat-caption">Menunggu modul pemesanan</div></div>
        <div class="card"><div class="stat-label">Feedback baru</div><div class="stat-value">—</div><div class="stat-caption">Menunggu modul feedback</div></div>
    </div>
    <div class="quick-grid">
        <div class="quick"><div class="quick-icon">♙</div><strong>Manajemen pelanggan</strong><p>Mengelola profil, kontak, dan riwayat interaksi pelanggan.</p></div>
        <div class="quick"><div class="quick-icon">▣</div><strong>Pemesanan</strong><p>Memproses pesanan dan memperbarui statusnya secara terstruktur.</p></div>
        <div class="quick"><div class="quick-icon">✳</div><strong>Promosi & loyalitas</strong><p>Mempersiapkan promosi untuk membantu mempertahankan pelanggan.</p></div>
    </div>
@endsection
