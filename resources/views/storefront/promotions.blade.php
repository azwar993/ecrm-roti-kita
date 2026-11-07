@extends('layouts.storefront')
@section('title', 'Promo RotiKita')
@section('content')
<main class="rks-content-page">
    <header class="rks-content-hero"><p class="rks-eyebrow">Penawaran untukmu</p><h1>Promo dari <em>RotiKita</em></h1><p>Promo aktif yang dapat digunakan pada pesanan sesuai syarat dan ketentuan.</p></header>
    @forelse($promotions as $promotion)
        <article class="rks-promotion-card">
            <div><span class="rks-promotion-code">{{ $promotion->code }}</span><h2>{{ $promotion->name }}</h2><p>{{ $promotion->description ?: 'Gunakan kode promo ini saat checkout.' }}</p><small>Berlaku sampai {{ $promotion->ends_at->translatedFormat('d F Y H:i') }}@if((float) $promotion->min_order_amount > 0) · Minimum belanja Rp {{ number_format((float) $promotion->min_order_amount, 0, ',', '.') }}@endif</small></div>
            <strong>{{ $promotion->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2), '0'), '.').'%' : 'Rp '.number_format((float) $promotion->discount_value, 0, ',', '.') }}</strong>
        </article>
    @empty
        <div class="rks-content-empty"><h2>Belum ada promo aktif</h2><p>Promo RotiKita akan tampil di sini saat tersedia.</p></div>
    @endforelse
    {{ $promotions->links() }}
</main>
@endsection
