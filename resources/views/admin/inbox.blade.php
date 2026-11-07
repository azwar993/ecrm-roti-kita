@extends('layouts.app')
@section('title', 'Inbox Admin')
@section('breadcrumb', 'Inbox / Notifikasi')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Notifikasi internal</p><h1>Inbox</h1><p>Pesanan baru dan perubahan status pembayaran customer.</p></div><form method="POST" action="{{ route('admin.inbox.read-all') }}">@csrf @method('PATCH')<button class="rk-btn" type="submit">Tandai semua dibaca</button></form></div>
<section class="rk-panel rk-panel-v2"><form class="rk-toolbar" method="GET"><select class="rk-select" name="filter"><option value="all" @selected($filter==='all')>Semua</option><option value="unread" @selected($filter==='unread')>Belum dibaca</option><option value="read" @selected($filter==='read')>Sudah dibaca</option></select><button class="rk-btn rk-btn-small">Filter</button></form>
<div class="rk-table-wrap"><table class="rk-table"><thead><tr><th>Notifikasi</th><th>Pesanan</th><th>Metode</th><th>Nominal</th><th>Waktu</th><th>Aksi</th></tr></thead><tbody>
@forelse($notifications as $notification)
<tr><td>{{ $notification->data['message'] ?? 'Notifikasi pembayaran' }} @if(!$notification->read_at)<span class="rk-badge rk-badge-active">Baru</span>@endif</td><td>{{ $notification->data['customer_name'] ?? 'Customer' }} · {{ $notification->data['order_number'] ?? '' }}</td><td>{{ strtoupper($notification->data['method'] ?? '') }}</td><td>Rp {{ number_format((float)($notification->data['amount'] ?? 0),0,',','.') }}</td><td>{{ $notification->created_at->format('d/m/Y H:i') }}</td><td><form method="POST" action="{{ route('admin.inbox.open',$notification->id) }}">@csrf<button class="rk-table-action">Lihat detail</button></form></td></tr>
@empty<tr><td colspan="6"><div class="rk-real-empty">Belum ada notifikasi pesanan atau pembayaran.</div></td></tr>@endforelse
</tbody></table></div><div class="rk-real-pagination">{{ $notifications->links() }}</div></section>
@endsection
