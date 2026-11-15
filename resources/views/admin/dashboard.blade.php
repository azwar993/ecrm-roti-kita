@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('breadcrumb', 'Dashboard')
@section('content')
@php
    $statusLabels = ['pending' => 'Menunggu', 'confirmed' => 'Dikonfirmasi', 'preparing' => 'Diproses', 'ready' => 'Siap diambil', 'completed' => 'Selesai', 'cancelled' => 'Dibatalkan'];
    $statusColors = ['pending' => 'gold', 'confirmed' => 'terra', 'preparing' => 'cocoa', 'ready' => 'sage', 'completed' => 'green', 'cancelled' => 'muted'];
    $chartValues = array_values($revenueChart);
    $safeChartValues = $chartValues ?: [0];
    $hasChartRevenue = count($chartValues) > 0 && array_sum(array_map('abs', $chartValues)) > 0;
    $chartMin = min(0, min($safeChartValues));
    $chartMaxValue = max(1, max($safeChartValues));
    $chartRange = max(1, $chartMaxValue - $chartMin);
    $chartPoints = [];
    $chartCount = max(1, count($chartValues) - 1);
    foreach ($chartValues as $index => $amount) {
        $x = 20 + ($index * 560 / $chartCount);
        $y = 190 - (((float) $amount - $chartMin) / $chartRange * 148);
        $chartPoints[] = round($x, 1).','.round($y, 1);
    }
    $chartPointList = implode(' ', $chartPoints);
    $chartAreaPoints = '20,198 '.$chartPointList.' 580,198';
    $periodLabels = array_keys($revenueChart);
@endphp
<div class="rk-rebuild-heading">
    <div><p class="rk-eyebrow">Ruang kerja admin</p><h1>Dashboard</h1><p>Ringkasan operasional dan performa RotiKita hari ini.</p></div>
    <div class="rk-rebuild-heading-tools"><form action="{{ route('admin.dashboard') }}" method="GET"><label for="admin-dashboard-period">Periode grafik</label><select id="admin-dashboard-period" name="period" class="rk-select" onchange="this.form.submit()"><option value="7" @selected($revenueChartPeriod === 7)>7 hari terakhir</option><option value="30" @selected($revenueChartPeriod === 30)>30 hari terakhir</option></select><noscript><button class="rk-btn rk-btn-small" type="submit">Terapkan</button></noscript></form><a class="rk-btn rk-btn-small" href="{{ route('admin.orders') }}">Kelola pesanan</a></div>
</div>

<section class="rk-rebuild-kpi-grid" aria-label="Indikator utama">
    <x-dashboard.kpi-card label="Pendapatan hari ini" :value="'Rp '.number_format((float) $todayRevenue, 0, ',', '.')" :meta="number_format($todayOrderCount).' pesanan dibuat hari ini'" icon="Rp" tone="terra" />
    <x-dashboard.kpi-card label="Total pesanan" :value="number_format($totalOrders)" :meta="number_format($monthOrderCount).' pesanan bulan ini'" icon="01" tone="gold" />
    <x-dashboard.kpi-card label="Perlu diproses" :value="number_format($pendingOrders)" meta="Menunggu hingga siap diambil" icon="↻" tone="sage" />
    <x-dashboard.kpi-card label="Total pelanggan" :value="number_format($customerCount)" meta="Profil CRM RotiKita" icon="♡" tone="cocoa" />
</section>
<aside class="rk-admin-payment-notice"><span class="rk-admin-payment-icon" aria-hidden="true">!</span><span><strong>Menunggu pembayaran</strong><small>{{ number_format($pendingVerificationCount) }} bukti menunggu verifikasi · {{ number_format($pendingPaymentCount) }} pembayaran belum lunas</small></span><b>Rp {{ number_format((float) $pendingVerificationAmount, 0, ',', '.') }}</b></aside>

<section class="rk-rebuild-insights" aria-label="Analisis toko">
    <article class="rk-rebuild-panel rk-rebuild-revenue-panel">
        <div class="rk-rebuild-panel-head"><div><p class="rk-eyebrow">KINERJA TOKO</p><h2>Tren pendapatan</h2><p>Nilai bersih dari pembayaran dan refund tercatat.</p></div><span class="rk-rebuild-panel-period">{{ $revenueChartPeriod }} hari</span></div>
        @if($hasChartRevenue)
            <div class="rk-rebuild-chart-wrap"><svg class="rk-rebuild-line-chart" viewBox="0 0 600 220" role="img" aria-label="Grafik pendapatan bersih {{ $revenueChartPeriod }} hari">
                <defs><linearGradient id="revenue-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#a64b2a" stop-opacity=".22"/><stop offset="100%" stop-color="#a64b2a" stop-opacity="0"/></linearGradient></defs>
                <path d="M20 52H580M20 100H580M20 148H580M20 198H580" class="rk-rebuild-chart-gridline" />
                <polygon points="{{ $chartAreaPoints }}" fill="url(#revenue-fill)" />
                <polyline points="{{ $chartPointList }}" class="rk-rebuild-chart-line" />
                @foreach($chartValues as $index => $amount)
                    @php($pointX = 20 + ($index * 560 / $chartCount))
                    @php($pointY = 190 - (((float) $amount - $chartMin) / $chartRange * 148))
                    <circle cx="{{ round($pointX, 1) }}" cy="{{ round($pointY, 1) }}" r="{{ count($chartValues) > 14 && $index % 4 !== 0 ? 2.4 : 4 }}" class="rk-rebuild-chart-point"><title>{{ \Illuminate\Support\Carbon::parse($periodLabels[$index])->translatedFormat('d M') }} · Rp {{ number_format((float) $amount, 0, ',', '.') }}</title></circle>
                @endforeach
            </svg></div>
            <div class="rk-rebuild-chart-axis"><span>{{ \Illuminate\Support\Carbon::parse($periodLabels[0])->translatedFormat('d M') }}</span><span>{{ \Illuminate\Support\Carbon::parse($periodLabels[(int) floor((count($periodLabels) - 1) / 2)])->translatedFormat('d M') }}</span><span>{{ \Illuminate\Support\Carbon::parse(end($periodLabels))->translatedFormat('d M') }}</span></div>
            <div class="rk-rebuild-chart-foot"><span><i></i>Akumulasi periode</span><strong>Rp {{ number_format((float) array_sum($revenueChart), 0, ',', '.') }}</strong></div>
        @else
            <div class="rk-rebuild-empty"><span aria-hidden="true">↗</span><strong>Belum ada pendapatan</strong><p>Grafik akan mengikuti pembayaran yang berhasil tercatat.</p></div>
        @endif
    </article>

    <article class="rk-rebuild-panel rk-rebuild-status-panel">
        <div class="rk-rebuild-panel-head"><div><p class="rk-eyebrow">ALUR PESANAN</p><h2>Status pesanan</h2><p>Semua pesanan berdasarkan status saat ini.</p></div><a href="{{ route('admin.orders') }}" class="rk-rebuild-text-link">Detail →</a></div>
        <div class="rk-rebuild-donut-layout">
            <div class="rk-rebuild-donut" role="img" aria-label="{{ number_format(array_sum($orderStatusCounts)) }} pesanan dalam enam status">
                <svg viewBox="0 0 100 100" aria-hidden="true"><circle class="rk-rebuild-donut-track" cx="50" cy="50" r="39" />
                    @php($dashOffset = 0)
                    @foreach($statusLabels as $status => $label)
                        @php($segment = array_sum($orderStatusCounts) > 0 ? ($orderStatusCounts[$status] / array_sum($orderStatusCounts)) * 100 : 0)
                        <circle class="rk-rebuild-donut-segment is-{{ $statusColors[$status] }}" cx="50" cy="50" r="39" pathLength="100" stroke-dasharray="{{ number_format($segment, 3, '.', '') }} {{ number_format(100 - $segment, 3, '.', '') }}" stroke-dashoffset="-{{ number_format($dashOffset, 3, '.', '') }}" />
                        @php($dashOffset += $segment)
                    @endforeach
                </svg><div class="rk-rebuild-donut-total"><strong>{{ number_format(array_sum($orderStatusCounts)) }}</strong><small>pesanan</small></div>
            </div>
            <div class="rk-rebuild-status-legend">
                @foreach($statusLabels as $status => $label)
                    <a href="{{ route('admin.orders', ['status' => $status]) }}"><span><i class="is-{{ $statusColors[$status] }}"></i>{{ $label }}</span><strong>{{ number_format($orderStatusCounts[$status]) }}</strong></a>
                @endforeach
            </div>
        </div>
    </article>
</section>

<section class="rk-rebuild-operations" aria-label="Operasional terbaru">
    <article class="rk-rebuild-panel rk-rebuild-orders-panel">
        <div class="rk-rebuild-panel-head"><div><p class="rk-eyebrow">OPERASIONAL</p><h2>Pesanan terbaru</h2><p>Periksa pesanan yang baru masuk.</p></div><a href="{{ route('admin.orders') }}" class="rk-rebuild-text-link">Semua pesanan →</a></div>
        <div class="rk-rebuild-table-scroll"><table class="rk-rebuild-table"><thead><tr><th>Pesanan</th><th>Pelanggan</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
            @forelse($recentOrders as $order)
                @php($statusTone = match($order->status) { 'completed' => 'green', 'cancelled' => 'muted', 'ready' => 'sage', 'preparing' => 'cocoa', 'confirmed' => 'terra', default => 'gold' })
                <tr><td><strong>{{ $order->order_number }}</strong><small>{{ $order->created_at->format('d M Y · H:i') }}</small></td><td>{{ $order->customer_name_snapshot ?? $order->customer?->full_name ?? 'Pelanggan' }}</td><td><strong>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</strong></td><td><span class="rk-rebuild-badge is-{{ $statusTone }}">{{ $statusLabels[$order->status] ?? ucfirst(str_replace('_', ' ', $order->status)) }}</span></td><td><a class="rk-rebuild-row-action" href="{{ route('admin.orders.show', $order) }}" aria-label="Detail {{ $order->order_number }}">→</a></td></tr>
            @empty<tr><td colspan="5"><div class="rk-rebuild-empty rk-rebuild-empty-inline"><strong>Belum ada pesanan</strong><span>Pesanan yang tercatat akan muncul di sini.</span></div></td></tr>@endforelse
        </tbody></table></div>
    </article>

    <article class="rk-rebuild-panel rk-rebuild-stock-panel">
        <div class="rk-rebuild-panel-head"><div><p class="rk-eyebrow">PERSEDIAAN</p><h2>Stok perlu diperiksa</h2><p>{{ number_format($lowStockCount) }} produk dengan stok lima unit atau kurang.</p></div><a href="{{ route('admin.products') }}" class="rk-rebuild-text-link">Kelola →</a></div>
        <div class="rk-rebuild-stock-list">
            @forelse($lowStockProducts as $product)
                <a class="rk-rebuild-stock-item" href="{{ route('admin.products.show', $product) }}">
                    @if($product->displayImagePath())<img src="{{ asset('storage/'.$product->displayImagePath()) }}" alt="" loading="lazy">@else<span class="rk-rebuild-stock-placeholder" aria-hidden="true">R.</span>@endif
                    <span class="rk-rebuild-stock-copy"><strong>{{ $product->name }}</strong><small>{{ $product->sku }}</small></span><b class="{{ $product->stock_qty <= 2 ? 'is-critical' : '' }}">{{ number_format($product->stock_qty) }}<small>unit</small></b>
                </a>
            @empty<div class="rk-rebuild-empty"><span aria-hidden="true">✓</span><strong>Stok aman</strong><p>Tidak ada produk aktif yang perlu segera diisi ulang.</p></div>@endforelse
        </div>
    </article>
</section>
<section class="rk-rebuild-panel rk-admin-recent-customers">
    <div class="rk-rebuild-panel-head"><div><p class="rk-eyebrow">HUBUNGAN PELANGGAN</p><h2>Pelanggan terbaru</h2><p>Profil pelanggan yang paling baru terdaftar.</p></div><a href="{{ route('admin.customers') }}" class="rk-rebuild-text-link">Buka CRM →</a></div>
    <div class="rk-admin-customer-list">
        @forelse($recentCustomers as $customer)
            <a href="{{ route('admin.customers') }}" class="rk-admin-customer-item"><span class="rk-admin-customer-avatar" aria-hidden="true">{{ strtoupper(substr($customer->full_name, 0, 1)) }}</span><span><strong>{{ $customer->full_name }}</strong><small>{{ $customer->created_at?->format('d M Y') ?? 'Tanggal tidak tersedia' }}</small></span><b>{{ number_format($customer->orders_count) }} pesanan</b></a>
        @empty
            <div class="rk-rebuild-empty"><strong>Belum ada pelanggan terdaftar</strong><p>Data pelanggan akan tampil setelah tersedia.</p></div>
        @endforelse
    </div>
</section>
@endsection
