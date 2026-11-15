@extends('layouts.app')
@section('title', 'Cetak Laporan Penjualan')
@section('content')
<main class="revenue-print-report">
    <header class="revenue-print-heading"><strong>RotiKita Bakery</strong><h1>Laporan Penjualan</h1><p>Periode {{ $from->format('d/m/Y') }} – {{ $until->format('d/m/Y') }}</p><p>Filter status: {{ $orderStatusLabel }}</p><small>Dibuat {{ $createdAt->format('d/m/Y H:i') }}</small></header>
    <button class="rk-commerce-btn revenue-print-action" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    <section class="rk-stats rk-stats-five" aria-label="Ringkasan laporan">
        <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total order</div><div class="rk-stat-value">{{ number_format($orderCount) }}</div></article>
        <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Order paid</div><div class="rk-stat-value">{{ number_format($transactionCount) }}</div></article>
        <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total pembayaran</div><div class="rk-stat-value">Rp {{ number_format((float)$paidTotal,0,',','.') }}</div></article>
        <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Total refund</div><div class="rk-stat-value">Rp {{ number_format((float)$refundTotal,0,',','.') }}</div></article>
        <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Pendapatan bersih</div><div class="rk-stat-value">Rp {{ number_format((float)$totalRevenue,0,',','.') }}</div></article>
    </section>
    <section class="rk-panel rk-panel-v2 rk-mt-18"><h2>Rincian pembayaran</h2><div class="rk-table-wrap"><table class="rk-table"><thead><tr><th>Order</th><th>Tanggal order</th><th>Tanggal paid</th><th>Status order</th><th>Status payment</th><th>Nominal paid</th></tr></thead><tbody>
        @forelse($transactions as $payment)<tr><td>{{ $payment->order?->order_number ?? 'Order tidak tersedia' }}</td><td>{{ $payment->order?->created_at?->format('d/m/Y') }}</td><td>{{ ($payment->period_paid_at ?? $payment->paid_at)?->format('d/m/Y H:i') }}</td><td>{{ $payment->order?->status ?? '—' }}</td><td>{{ $payment->status }}</td><td>Rp {{ number_format((float)($payment->period_paid_amount ?? $payment->amount),0,',','.') }}</td></tr>@empty<tr><td colspan="6">Tidak ada pembayaran berhasil.</td></tr>@endforelse
    </tbody></table></div></section>
    <section class="rk-panel rk-panel-v2 rk-mt-18"><h2>Rincian refund per order</h2><div class="rk-table-wrap"><table class="rk-table"><thead><tr><th>Order</th><th>Tanggal refund</th><th>Status order</th><th>Status payment</th><th>Nominal refund</th></tr></thead><tbody>
        @forelse($refunds as $payment)<tr><td>{{ $payment->order?->order_number ?? 'Order tidak tersedia' }}</td><td>{{ ($payment->period_refund_at ?? $payment->refunded_at ?? $payment->verified_at ?? $payment->updated_at)?->format('d/m/Y H:i') }}</td><td>{{ $payment->order?->status ?? '—' }}</td><td>{{ $payment->status }}</td><td>Rp {{ number_format((float)($payment->period_refund_amount ?? $payment->amount),0,',','.') }}</td></tr>@empty<tr><td colspan="5">Tidak ada refund pada periode ini.</td></tr>@endforelse
    </tbody></table></div></section>
</main>
@endsection
