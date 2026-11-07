@extends('layouts.app')
@section('title', 'Detail Pelanggan')
@section('breadcrumb', 'Pelanggan / Detail')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Profil pelanggan</p><h1>{{ $customer->full_name }}</h1><p>Kode {{ $customer->customer_code }} · Dibuat {{ $customer->created_at->format('d M Y') }}</p></div><a class="rk-btn rk-btn-quiet" href="{{ route('admin.customers') }}">Kembali ke daftar</a></div>
<div class="rk-real-stats"><article class="rk-real-stat"><span>Seluruh pesanan</span><strong>{{ number_format($orders->total()) }}</strong><small>Riwayat profil ini</small></article><article class="rk-real-stat"><span>Total order selesai</span><strong>Rp {{ number_format((float)$completedSpend,0,',','.') }}</strong><small>Jumlah dari pesanan berstatus selesai</small></article><article class="rk-real-stat"><span>Consent email marketing</span><strong>{{ $customer->email_marketing_consent ? 'Diizinkan' : 'Tidak diizinkan' }}</strong><small>
@if($customer->consent_updated_at)
    Diperbarui {{ $customer->consent_updated_at->format('d M Y') }}
@else
    Belum ada waktu consent tersimpan
@endif
</small></article></div>
<section class="rk-panel rk-panel-v2"><div class="rk-panel-heading"><div><h2>Informasi kontak</h2><p>Nilai yang tersimpan pada profil CRM dan akun terkait.</p></div></div><div class="rk-real-form-grid"><div><strong>Email CRM</strong><p>{{ $customer->contact_email ?: '—' }}</p></div><div><strong>Email akun</strong><p>{{ $customer->user?->email ?: 'Tidak ada akun terhubung' }}</p></div><div><strong>Telepon</strong><p>{{ $customer->phone ?: '—' }}</p></div><div><strong>Alamat</strong><p>{{ $customer->address ?: '—' }}</p></div><div class="rk-real-full"><strong>Catatan</strong><p>{{ $customer->notes ?: 'Tidak ada catatan.' }}</p></div></div></section>
<section class="rk-panel rk-panel-v2 rk-mt-18"><div class="rk-panel-heading"><div><h2>Riwayat pesanan</h2><p>Order yang terkait dengan profil ini.</p></div></div><div class="rk-table-wrap"><table class="rk-table rk-data-table"><thead><tr><th>Nomor</th><th>Tanggal</th><th>Total</th><th>Status order</th><th>Pembayaran</th></tr></thead><tbody>
@forelse($orders as $order)<tr><td><strong>{{ $order->order_number }}</strong></td><td>{{ $order->created_at->format('d M Y · H:i') }}</td><td>Rp {{ number_format((float)$order->total_amount,0,',','.') }}</td><td>{{ ucfirst(str_replace('_',' ',$order->status)) }}</td><td>{{ $order->payment ? ucfirst(str_replace('_',' ',$order->payment->status)) : '—' }}</td></tr>@empty<tr><td colspan="5"><div class="rk-real-empty"><strong>Belum ada pesanan</strong><p>Belum ada order yang terkait dengan profil ini.</p></div></td></tr>@endforelse
</tbody></table></div><div class="rk-real-pagination">{{ $orders->links() }}</div></section>
<section class="rk-panel rk-panel-v2 rk-mt-18"><div class="rk-panel-heading"><div><h2>Feedback terbaru</h2><p>Masukan yang dikirim oleh pelanggan ini.</p></div></div>
@forelse($feedback as $item)<article class="rk-feedback-history"><div class="rk-panel-heading"><div><strong>{{ ucfirst($item->type) }}</strong><small class="rk-cell-sub">{{ $item->created_at->format('d M Y · H:i') }}@if($item->order) · {{ $item->order->order_number }}@endif</small></div><span>{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></div><p>{{ $item->message }}</p>@if($item->response)<small>Tanggapan: {{ $item->response }}</small>@endif</article>@empty<div class="rk-real-empty"><strong>Belum ada feedback</strong></div>@endforelse
</section>
@endsection
