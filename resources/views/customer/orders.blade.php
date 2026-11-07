@extends('layouts.app')
@section('title', 'Pesanan Saya')
@section('breadcrumb', 'Pesanan saya')
@section('content')
@include('components.commerce-styles')
<div class="rk-commerce">
    <div class="rk-commerce-head"><div><div class="rk-commerce-eyebrow">Riwayat transaksi</div><h1>Pesanan saya</h1><p>Riwayat pesanan yang terhubung ke akun pelanggan lo.</p></div><a class="rk-commerce-btn" href="{{ route('customer.catalog') }}">Pesan lagi</a></div>
    @if($orders->isEmpty())
        <section class="rk-commerce-panel rk-commerce-empty"><h3>Belum ada pesanan</h3><p>Pesanan yang berhasil dibuat akan muncul di halaman ini.</p><a class="rk-commerce-btn" href="{{ route('customer.catalog') }}">Buka katalog</a></section>
    @else
        <section class="rk-commerce-panel"><div class="rk-commerce-table-wrap"><table class="rk-commerce-table"><thead><tr><th>Pesanan</th><th>Tanggal</th><th>Jenis</th><th>Total</th><th>Status</th><th>Pembayaran</th><th>Aksi</th></tr></thead><tbody>
            @foreach($orders as $order)
                <tr><td><strong>{{ $order->order_number }}</strong>@if($order->pickup_code)<small>Kode pickup: {{ $order->pickup_code }}</small>@endif</td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td><td>{{ $order->order_type === 'dine_in' ? 'Dine-in' : 'Takeaway' }}</td><td><strong>Rp {{ number_format((float)$order->total_amount,0,',','.') }}</strong></td><td><span class="rk-commerce-pill {{ $order->status === 'completed' ? 'green' : ($order->status === 'cancelled' ? 'red' : 'orange') }}">{{ ucfirst(str_replace('_',' ',$order->status)) }}</span></td><td>{{ $order->payment ? ucfirst(str_replace('_',' ',$order->payment->status)) : '—' }}</td><td><a class="rk-commerce-btn ghost small" href="{{ route('customer.orders.show',$order) }}">Detail</a></td></tr>
            @endforeach
        </tbody></table></div><div style="margin-top:18px">{{ $orders->links() }}</div></section>
    @endif
</div>
@endsection
