@extends('layouts.app')
@section('title', 'Laporan Penjualan')
@section('breadcrumb', 'Laporan penjualan')
@section('content')
@php($statusLabels = ['pending' => 'Menunggu pembayaran', 'confirmed' => 'Dikonfirmasi', 'preparing' => 'Sedang diproses', 'ready' => 'Siap diambil', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan', 'paid' => 'Lunas', 'refunded' => 'Dikembalikan'])
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Keuangan</p><h1>Laporan penjualan</h1><p>Pendapatan mengikuti pembayaran berhasil dan refund yang tercatat pada ledger.</p></div></div>
<section class="rk-panel rk-panel-v2">
    <form method="GET" action="{{ route('admin.reports.revenue') }}" class="rk-commerce-form-grid rk-revenue-filter">
        <label class="rk-commerce-field">Periode<select class="rk-commerce-select" name="period" id="revenue-period"><option value="today" @selected($period==='today')>Hari ini</option><option value="last7" @selected($period==='last7')>7 hari terakhir</option><option value="this_month" @selected($period==='this_month')>Bulan ini</option><option value="last_month" @selected($period==='last_month')>Bulan sebelumnya</option><option value="custom" @selected($period==='custom')>Rentang tanggal</option></select></label>
        <label class="rk-commerce-field">Dari tanggal<input class="rk-commerce-input" type="date" name="from" value="{{ request('from', $from->toDateString()) }}"></label>
        <label class="rk-commerce-field">Sampai tanggal<input class="rk-commerce-input" type="date" name="until" value="{{ request('until', $until->toDateString()) }}"></label>
        <label class="rk-commerce-field">Status pesanan<select class="rk-commerce-select" name="order_status"><option value="all" @selected(!$orderStatus)>Semua status</option>@foreach($orderStatuses as $status)<option value="{{ $status }}" @selected($orderStatus === $status)>{{ $statusLabels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></label>
        <div class="rk-revenue-filter-actions">
            <button class="rk-commerce-btn" type="submit">Terapkan</button>
            <a class="rk-commerce-btn" href="{{ route('admin.reports.revenue.export', request()->query()) }}">Unduh CSV</a>
            <a class="rk-commerce-btn rk-commerce-btn-secondary" href="{{ route('admin.reports.revenue.print', request()->query()) }}" target="_blank" rel="noopener">Cetak hasil / Simpan PDF</a>
        </div>
    </form>
</section>
<section class="rk-stats rk-stats-five rk-mt-18" aria-label="Ringkasan penjualan">
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Pendapatan bersih</div><div class="rk-stat-value rk-stat-value-money">Rp {{ number_format((float)$totalRevenue,0,',','.') }}</div><div class="rk-stat-note">{{ $from->format('d/m/Y') }} – {{ $until->format('d/m/Y') }}</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total pesanan</div><div class="rk-stat-value">{{ number_format($orderCount) }}</div><div class="rk-stat-note">{{ $orderStatus ? ($statusLabels[$orderStatus] ?? str_replace('_', ' ', $orderStatus)) : 'semua status' }}</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Pembayaran lunas</div><div class="rk-stat-value">{{ number_format($transactionCount) }}</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total pembayaran</div><div class="rk-stat-value rk-stat-value-money">Rp {{ number_format((float)$paidTotal,0,',','.') }}</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total refund</div><div class="rk-stat-value rk-stat-value-money">Rp {{ number_format((float)$refundTotal,0,',','.') }}</div></article>
</section>
<section class="rk-panel rk-panel-v2 rk-mt-18"><div class="rk-panel-heading"><div><h2>Pendapatan harian</h2><p>Pembayaran lunas dikurangi refund pada tanggal pencatatan.</p></div></div>
@php($maxDaily = max(1, ...array_values($dailyRevenue->all())))
<div style="display:grid;gap:10px">@foreach($dailyRevenue as $date=>$amount)<div style="display:grid;grid-template-columns:minmax(88px,120px) 1fr minmax(100px,150px);gap:12px;align-items:center"><span>{{ \Illuminate\Support\Carbon::parse($date)->format('d M') }}</span><div style="height:12px;background:#f1e7da;border-radius:99px;overflow:hidden"><div style="height:100%;width:{{ min(100, (max(0, (float)$amount) / max(1, (float)$dailyRevenue->max())) * 100) }}%;background:#b85c3b;border-radius:99px"></div></div><strong>Rp {{ number_format((float)$amount,0,',','.') }}</strong></div>@endforeach</div>
</section>
<section class="rk-panel rk-panel-v2 rk-mt-18"><div class="rk-panel-heading"><div><h2>Pembayaran berhasil</h2><p>Daftar pembayaran yang masuk dalam periode dan status pilihan.</p></div></div>
<div class="rk-table-wrap"><table class="rk-table rk-data-table"><thead><tr><th>Nomor order</th><th>Tanggal order</th><th>Event paid</th><th>Status pembayaran</th><th>Nominal paid</th><th></th></tr></thead><tbody>
@forelse($transactions as $payment)<tr><td>{{ $payment->order?->order_number ?? 'Pesanan tidak tersedia' }}</td><td>{{ $payment->order?->created_at?->format('d/m/Y') }}</td><td>{{ ($payment->period_paid_at ?? $payment->paid_at)?->format('d/m/Y H:i') }}</td><td>{{ $statusLabels[$payment->status] ?? ucfirst($payment->status) }}</td><td><strong>Rp {{ number_format((float)($payment->period_paid_amount ?? $payment->amount),0,',','.') }}</strong></td><td>@if($payment->order)<a class="rk-table-action" href="{{ route('admin.orders.show',$payment->order) }}">Detail</a>@endif</td></tr>@empty<tr><td colspan="6">Tidak ada pembayaran berhasil pada periode ini.</td></tr>@endforelse
</tbody></table></div><div class="rk-real-pagination">{{ $transactions->links() }}</div></section>
<section class="rk-panel rk-panel-v2 rk-mt-18"><div class="rk-panel-heading"><div><h2>Refund</h2><p>Refund dicatat per order berdasarkan event refund yang sah.</p></div></div>
<div class="rk-table-wrap"><table class="rk-table rk-data-table"><thead><tr><th>Nomor order</th><th>Tanggal refund</th><th>Status order</th><th>Nominal refund</th><th></th></tr></thead><tbody>
@forelse($refunds as $payment)<tr><td>{{ $payment->order?->order_number ?? 'Order tidak tersedia' }}</td><td>{{ ($payment->period_refund_at ?? $payment->refunded_at ?? $payment->verified_at ?? $payment->updated_at)?->format('d/m/Y H:i') }}</td><td>{{ $payment->order?->status ? ucfirst(str_replace('_', ' ', $payment->order->status)) : '—' }}</td><td><strong>Rp {{ number_format((float)($payment->period_refund_amount ?? $payment->amount),0,',','.') }}</strong></td><td>@if($payment->order)<a class="rk-table-action" href="{{ route('admin.orders.show',$payment->order) }}">Detail</a>@endif</td></tr>@empty<tr><td colspan="5">Tidak ada refund pada periode ini.</td></tr>@endforelse
</tbody></table></div><div class="rk-real-pagination">{{ $refunds->links() }}</div></section>
@endsection
