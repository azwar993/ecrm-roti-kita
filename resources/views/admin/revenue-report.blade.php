@extends('layouts.app')
@section('title', 'Laporan Pendapatan')
@section('breadcrumb', 'Laporan pendapatan')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Keuangan</p><h1>Laporan pendapatan</h1><p>Hanya pembayaran berstatus paid berdasarkan waktu pembayaran, setelah dikurangi refund.</p></div></div>
<section class="rk-panel rk-panel-v2">
    <form method="GET" action="{{ route('admin.reports.revenue') }}" class="rk-commerce-form-grid">
        <label class="rk-commerce-field">Periode<select class="rk-commerce-select" name="period" id="revenue-period"><option value="today" @selected($period==='today')>Hari ini</option><option value="last7" @selected($period==='last7')>7 hari terakhir</option><option value="this_month" @selected($period==='this_month')>Bulan ini</option><option value="last_month" @selected($period==='last_month')>Bulan sebelumnya</option><option value="custom" @selected($period==='custom')>Rentang tanggal</option></select></label>
        <label class="rk-commerce-field">Dari tanggal<input class="rk-commerce-input" type="date" name="from" value="{{ request('from', $from->toDateString()) }}"></label>
        <label class="rk-commerce-field">Sampai tanggal<input class="rk-commerce-input" type="date" name="until" value="{{ request('until', $until->toDateString()) }}"></label>
        <button class="rk-commerce-btn" type="submit">Terapkan</button>
    </form>
</section>
<section class="rk-stats rk-stats-four rk-mt-18">
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Pendapatan bersih</div><div class="rk-stat-value rk-stat-value-money">Rp {{ number_format((float)$totalRevenue,0,',','.') }}</div><div class="rk-stat-note">{{ $from->format('d/m/Y') }} – {{ $until->format('d/m/Y') }}</div></article>
    <article class="rk-stat rk-stat-v2"><div class="rk-stat-label">Pembayaran berhasil</div><div class="rk-stat-value">{{ number_format($transactionCount) }}</div><div class="rk-stat-note">Satu hitungan per payment</div></article>
</section>
<section class="rk-panel rk-panel-v2 rk-mt-18"><div class="rk-panel-heading"><div><h2>Pendapatan harian</h2><p>Grafik berdasarkan paid_at dari database.</p></div></div>
@php($maxDaily = max(1, ...array_values($dailyRevenue->all())))
<div style="display:grid;gap:10px">@foreach($dailyRevenue as $date=>$amount)<div style="display:grid;grid-template-columns:minmax(88px,120px) 1fr minmax(100px,150px);gap:12px;align-items:center"><span>{{ \Illuminate\Support\Carbon::parse($date)->format('d M') }}</span><div style="height:12px;background:#f1e7da;border-radius:99px;overflow:hidden"><div style="height:100%;width:{{ min(100, ((float)$amount / $maxDaily) * 100) }}%;background:#b85c3b;border-radius:99px"></div></div><strong>Rp {{ number_format((float)$amount,0,',','.') }}</strong></div>@endforeach</div>
</section>
<section class="rk-panel rk-panel-v2 rk-mt-18"><div class="rk-panel-heading"><div><h2>Pembayaran terkonfirmasi</h2><p>Transaksi dalam rentang periode yang dipilih.</p></div></div>
<div class="rk-table-wrap"><table class="rk-table rk-data-table"><thead><tr><th>Nomor pesanan</th><th>Waktu dibayar</th><th>Metode</th><th>Nominal</th><th></th></tr></thead><tbody>
@forelse($transactions as $payment)<tr><td>{{ $payment->order?->order_number ?? 'Order dihapus' }}</td><td>{{ $payment->paid_at?->format('d/m/Y H:i') }}</td><td>{{ strtoupper(str_replace('_',' ',$payment->method)) }}</td><td><strong>Rp {{ number_format((float)$payment->amount,0,',','.') }}</strong></td><td>@if($payment->order)<a class="rk-table-action" href="{{ route('admin.orders.show',$payment->order) }}">Detail</a>@endif</td></tr>@empty<tr><td colspan="5">Tidak ada pembayaran paid pada periode ini.</td></tr>@endforelse
</tbody></table></div><div class="rk-real-pagination">{{ $transactions->links() }}</div></section>
@endsection
