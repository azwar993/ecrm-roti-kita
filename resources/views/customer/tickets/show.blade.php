@extends('layouts.app')
@section('title', $ticket->ticket_number)
@section('breadcrumb', 'Pusat Bantuan')
@section('content')
<div class="rk-page-heading"><div><p class="rk-eyebrow">{{ $ticket->ticket_number }} · {{ str_replace('_', ' ', ucfirst($ticket->status)) }}</p><h1>{{ $ticket->subject }}</h1></div><a class="rk-btn rk-btn-quiet" href="{{ route('customer.tickets.index') }}">Kembali</a></div>
<section class="rk-panel rk-panel-v2"><p>Kategori: {{ $ticket->category }} · Prioritas: {{ $ticket->priority }}</p>@foreach($ticket->messages as $message)<article class="rk-panel rk-mt-18"><strong>{{ $message->user->role === 'admin' ? 'Tim RotiKita' : 'Anda' }}</strong><time>{{ $message->created_at->format('d M Y H:i') }}</time><p>{{ $message->message }}</p>@foreach($message->attachments as $attachment)<a href="{{ route('customer.tickets.attachments.download', $attachment) }}">Lampiran: {{ $attachment->original_name }}</a>@endforeach</article>@endforeach
@if($ticket->status !== 'closed')<form class="rk-mt-18" method="POST" enctype="multipart/form-data" action="{{ route('customer.tickets.reply', $ticket) }}">@csrf<label class="rk-field"><span>Balas</span><textarea name="message" rows="4" maxlength="10000" required></textarea></label><label class="rk-field"><span>Lampiran opsional (maks. 5 MB)</span><input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"></label><button class="rk-btn" type="submit">Kirim balasan</button></form>@else<p>Tiket telah ditutup dan tidak menerima balasan.</p>@endif</section>
@endsection
