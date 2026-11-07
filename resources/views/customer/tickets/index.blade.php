@extends('layouts.app')
@section('title', 'Pusat Bantuan')
@section('breadcrumb', 'Pusat Bantuan')
@section('content')
<div class="rk-page-heading"><div><p class="rk-eyebrow">Bantuan RotiKita</p><h1>Tiket saya</h1></div><a class="rk-btn" href="{{ route('customer.tickets.create') }}">Buat tiket</a></div>
<section class="rk-panel rk-panel-v2"><div class="rk-table-wrap"><table class="rk-table"><thead><tr><th>Nomor</th><th>Subjek</th><th>Status</th><th>Dibuat</th><th></th></tr></thead><tbody>
@forelse($tickets as $ticket)<tr><td>{{ $ticket->ticket_number }}</td><td>{{ $ticket->subject }}</td><td>{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</td><td>{{ $ticket->created_at->format('d M Y') }}</td><td><a class="rk-table-action" href="{{ route('customer.tickets.show', $ticket) }}">Buka</a></td></tr>@empty<tr><td colspan="5">Belum ada tiket bantuan.</td></tr>@endforelse
</tbody></table></div>{{ $tickets->links() }}</section>
@endsection
