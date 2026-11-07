@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('breadcrumb', 'Dashboard')
@section('content')
<div class="rk-page-heading rk-page-heading-v2">
    <div><p class="rk-eyebrow">Ruang kerja admin</p><h1>Dashboard</h1><p>Pantau transaksi, pembayaran, dan aktivitas pelanggan.</p></div>
    <div class="rk-heading-actions"><a class="rk-btn rk-btn-small" href="{{ route('admin.orders') }}">Lihat pesanan</a><a class="rk-btn rk-btn-small" href="{{ route('admin.reports.revenue') }}">Laporan pendapatan</a></div>
</div>
<section class="rk-stats rk-stats-four" aria-label="Ringkasan transaksi">
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total pendapatan terkonfirmasi</div><div class="rk-stat-value rk-stat-value-money">Rp {{ number_format((float) $totalRevenue, 0, ',', '.') }}</div><div class="rk-stat-note">Pembayaran paid, tidak termasuk refund</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Pendapatan bulan ini</div><div class="rk-stat-value rk-stat-value-money">Rp {{ number_format((float) $monthRevenue, 0, ',', '.') }}</div><div class="rk-stat-note">{{ number_format($monthSuccessfulTransactions) }} pembayaran berhasil</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Menunggu verifikasi</div><div class="rk-stat-value">{{ number_format($pendingVerificationCount) }}</div><div class="rk-stat-note">Rp {{ number_format((float) $pendingVerificationAmount, 0, ',', '.') }}</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Menunggu pembayaran</div><div class="rk-stat-value">{{ number_format($pendingPaymentCount) }}</div><div class="rk-stat-note">Belum dihitung sebagai pendapatan</div></article>
</section>
<section class="rk-stats rk-stats-four rk-mt-18" aria-label="Ringkasan CRM">
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total pelanggan</div><div class="rk-stat-value">{{ number_format($customerCount) }}</div><div class="rk-stat-note">Data terdaftar</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Pesanan bulan ini</div><div class="rk-stat-value">{{ number_format($monthOrderCount) }}</div><div class="rk-stat-note">Berdasarkan tanggal dibuat</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Feedback pelanggan</div><div class="rk-stat-value">{{ number_format($feedbackCount) }}</div><div class="rk-stat-note">{{ number_format($unresolvedFeedbackCount) }} perlu ditinjau</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Transaksi berhasil</div><div class="rk-stat-value">{{ number_format($monthSuccessfulTransactions) }}</div><div class="rk-stat-note">Pembayaran dikonfirmasi bulan ini</div></article>
</section>
<section class="rk-stats rk-stats-four rk-mt-18" aria-label="Indikator hubungan pelanggan bulan ini">
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Customer pembeli</div><div class="rk-stat-value">{{ number_format($monthActiveCustomers) }}</div><div class="rk-stat-note">Customer unik dengan pembayaran paid bulan ini</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Repeat purchase rate</div><div class="rk-stat-value">{{ number_format($monthRepeatPurchaseRate, 1) }}%</div><div class="rk-stat-note">Buyer dengan ≥2 order paid ÷ buyer bulan ini</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Rata-rata order paid</div><div class="rk-stat-value rk-stat-value-money">Rp {{ number_format($monthAverageOrderValue, 0, ',', '.') }}</div><div class="rk-stat-note">Nilai pembayaran paid ÷ jumlah order paid</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Tiket terbuka</div><div class="rk-stat-value">{{ number_format($openSupportTicketCount) }}</div><div class="rk-stat-note">Open, in progress, atau menunggu customer</div></article>
</section>
<div class="rk-dashboard-grid">
    <section class="rk-panel rk-panel-v2">
        <div class="rk-panel-heading"><div><h2>Perlu ditindaklanjuti</h2><p>Angka berasal dari data pembayaran dan operasional.</p></div></div>
        <a class="rk-priority-row" href="{{ route('admin.inbox', ['filter' => 'unread']) }}"><span class="rk-priority-copy"><strong>Inbox pembayaran</strong><small>Bukti menunggu pemeriksaan</small></span><b>{{ number_format($pendingVerificationCount) }}</b></a>
        <a class="rk-priority-row" href="{{ route('admin.orders') }}"><span class="rk-priority-copy"><strong>Pesanan berjalan</strong><small>Menunggu hingga siap</small></span><b>{{ number_format($pendingOrders) }}</b></a>
        <a class="rk-priority-row" href="{{ route('admin.feedback') }}"><span class="rk-priority-copy"><strong>Feedback belum selesai</strong><small>Baru atau sedang ditangani</small></span><b>{{ number_format($unresolvedFeedbackCount) }}</b></a>
        <a class="rk-priority-row" href="{{ route('admin.products') }}"><span class="rk-priority-copy"><strong>Produk stok rendah</strong><small>Stok aktif 5 unit atau kurang</small></span><b>{{ number_format($lowStockCount) }}</b></a>
    </section>
</div>
<section class="rk-panel rk-panel-v2 rk-mt-18">
    <div class="rk-panel-heading"><div><h2>Transaksi terbaru</h2><p>Order dan status pembayaran aktual.</p></div><a class="rk-link" href="{{ route('admin.orders') }}">Lihat semua</a></div>
    <div class="rk-table-wrap"><table class="rk-table rk-data-table"><thead><tr><th>No. pesanan</th><th>Pelanggan</th><th>Total</th><th>Status pesanan</th><th>Pembayaran</th><th></th></tr></thead><tbody>
    @forelse ($recentOrders as $order)
        <tr><td><strong>{{ $order->order_number }}</strong><small class="rk-cell-sub">{{ $order->created_at->format('d M Y · H:i') }}</small></td><td>{{ $order->customer_name_snapshot ?? $order->customer?->full_name ?? '—' }}</td><td><strong>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</strong></td><td>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</td><td>{{ $order->payment ? ucfirst(str_replace('_', ' ', $order->payment->status)) : '—' }}</td><td><a class="rk-table-action" href="{{ route('admin.orders.show', $order) }}">Detail</a></td></tr>
    @empty
        <tr><td colspan="6">Belum ada transaksi.</td></tr>
    @endforelse
    </tbody></table></div>
</section>
@endsection
