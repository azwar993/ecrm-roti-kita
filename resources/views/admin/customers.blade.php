@extends('layouts.app')
@section('title', 'Manajemen Pelanggan')
@section('breadcrumb', 'Pelanggan')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Customer relationship</p><h1>Manajemen pelanggan</h1><p>Profil, consent, dan aktivitas yang tersimpan di database.</p></div></div>
<div class="rk-real-stats">
    <article class="rk-real-stat"><span>Total profil</span><strong>{{ number_format($totalCustomers) }}</strong><small>Semua record customer</small></article>
    <article class="rk-real-stat"><span>Memiliki pesanan</span><strong>{{ number_format($customersWithOrders) }}</strong><small>Profil dengan riwayat order</small></article>
    <article class="rk-real-stat"><span>Profil baru bulan ini</span><strong>{{ number_format($newThisMonth) }}</strong><small>Dihitung dari waktu pembuatan profil</small></article>
</div>
<section class="rk-panel rk-panel-v2 rk-real-panel">
    <div class="rk-panel-heading"><div><h2>Daftar pelanggan</h2><p>Email consent mengikuti pilihan yang tersimpan pada profil.</p></div></div>
    <form class="rk-real-toolbar" method="GET" action="{{ route('admin.customers') }}">
        <label class="rk-search"><span aria-hidden="true">⌕</span><input name="q" type="search" value="{{ $search }}" placeholder="Nama, email, kode, atau telepon"></label>
        <select class="rk-select" name="filter" aria-label="Filter pelanggan">
            <option value="all" @selected($filter==='all')>Semua profil</option>
            <option value="account" @selected($filter==='account')>Akun terhubung</option>
            <option value="no_account" @selected($filter==='no_account')>Tanpa akun</option>
            <option value="consent_yes" @selected($filter==='consent_yes')>Consent email: ya</option>
            <option value="consent_no" @selected($filter==='consent_no')>Consent email: tidak</option>
            <option value="with_orders" @selected($filter==='with_orders')>Memiliki pesanan</option>
            <option value="without_orders" @selected($filter==='without_orders')>Belum memiliki pesanan</option>
        </select>
        <button class="rk-btn rk-btn-small" type="submit">Terapkan</button>
        <a class="rk-btn rk-btn-quiet rk-btn-small" href="{{ route('admin.customers') }}">Reset</a>
    </form>
    <div class="rk-table-wrap"><table class="rk-table rk-data-table rk-real-table"><thead><tr><th>Pelanggan</th><th>Kode</th><th>Telepon</th><th>Pesanan</th><th>Terakhir pesan</th><th>Email marketing</th><th></th></tr></thead><tbody>
    @forelse($customers as $customer)
        <tr>
            <td><div class="rk-person-cell"><span class="rk-avatar-initial">{{ mb_strtoupper(mb_substr($customer->full_name,0,1)) }}</span><span><strong>{{ $customer->full_name }}</strong><small>{{ $customer->contact_email ?: $customer->user?->email ?: 'Email tidak tersedia' }}</small></span></div></td>
            <td><span class="rk-mono">{{ $customer->customer_code }}</span><small class="rk-cell-sub">{{ $customer->user ? 'Akun terhubung' : 'Profil tanpa akun' }}</small></td>
            <td>{{ $customer->phone ?: '—' }}</td>
            <td><strong>{{ number_format($customer->orders_count) }}</strong><small class="rk-cell-sub">{{ number_format($customer->completed_orders_count) }} selesai</small></td>
            <td>{{ $customer->orders_max_created_at ? \Illuminate\Support\Carbon::parse($customer->orders_max_created_at)->format('d M Y') : 'Belum ada pesanan' }}</td>
            <td><span class="rk-badge {{ $customer->email_marketing_consent ? 'rk-badge-active' : '' }}">{{ $customer->email_marketing_consent ? 'Diizinkan' : 'Tidak diizinkan' }}</span>@if($customer->consent_updated_at)<small class="rk-cell-sub">{{ $customer->consent_updated_at->format('d M Y') }}</small>@endif</td>
            <td><a class="rk-table-action" href="{{ route('admin.customers.show',$customer) }}">Detail</a></td>
        </tr>
    @empty
        <tr><td colspan="7"><div class="rk-real-empty"><strong>{{ $search || $filter!=='all' ? 'Tidak ada pelanggan yang cocok' : 'Belum ada profil pelanggan' }}</strong><p>{{ $search || $filter!=='all' ? 'Ubah kata pencarian atau filter.' : 'Profil akan tercatat saat tersedia di CRM. Tidak ada data contoh.' }}</p>@if($search || $filter!=='all')<a class="rk-btn rk-btn-quiet rk-btn-small" href="{{ route('admin.customers') }}">Reset filter</a>@endif</div></td></tr>
    @endforelse
    </tbody></table></div>
    <div class="rk-real-pagination">{{ $customers->links() }}</div>
</section>
@endsection
