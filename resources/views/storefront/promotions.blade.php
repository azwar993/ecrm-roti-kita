@extends('layouts.storefront')
@section('title', 'Promo RotiKita')
@section('content')
<main class="rks-content-page">
    <header class="rks-promo-hero">
        <div class="rks-promo-hero-copy"><p class="rks-eyebrow">Penawaran untukmu</p><h1>Lebih banyak alasan<br>untuk <em>menikmati.</em></h1><p>Temukan penawaran RotiKita yang sedang aktif. Masukkan kode promo saat checkout sesuai ketentuannya.</p><a class="rks-btn rks-btn-primary" href="{{ route('storefront.menu') }}">Jelajahi menu <span aria-hidden="true">→</span></a></div>
        <div class="rks-promo-hero-visual">
            @if($promoVisual?->displayImagePath())<img src="{{ asset('storage/'.$promoVisual->displayImagePath()) }}" alt="Pilihan bakery RotiKita" fetchpriority="high">@else<span class="rks-promo-hero-placeholder" aria-hidden="true">RotiKita<br><small>Bakery & Cafe</small></span>@endif
            <span>Manisnya hari, lebih istimewa</span>
        </div>
    </header>
    <div class="rks-promo-list-heading"><div><p class="rks-eyebrow">Pilih penawaran</p><h2>Promo <em>aktif.</em></h2></div><span>{{ $promotions->total() }} penawaran tersedia</span></div>
    @forelse($promotions as $promotion)
        <article class="rks-promotion-card">
            <div class="rks-promotion-copy"><span class="rks-promotion-code">{{ $promotion->code }}</span><h2>{{ trim(strtoupper($promotion->name)) === trim(strtoupper($promotion->code)) ? 'Promo spesial RotiKita' : $promotion->name }}</h2><p>{{ $promotion->description ?: 'Gunakan kode promo ini saat checkout.' }}</p><small>@if($promotion->starts_at){{ $promotion->starts_at->translatedFormat('d M Y') }} – @endif{{ $promotion->ends_at->translatedFormat('d F Y H:i') }}@if((float) $promotion->min_order_amount > 0) · Minimum belanja Rp {{ number_format((float) $promotion->min_order_amount, 0, ',', '.') }}@endif</small></div>
            <div class="rks-promotion-reward"><strong>{{ $promotion->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2), '0'), '.').'%' : 'Rp '.number_format((float) $promotion->discount_value, 0, ',', '.') }}</strong><a href="{{ route('storefront.menu') }}">Belanja dengan promo <span aria-hidden="true">→</span></a></div>
        </article>
    @empty
        <div class="rks-content-empty"><h2>Belum ada promo aktif</h2><p>Promo RotiKita akan tampil di sini saat tersedia.</p></div>
    @endforelse
    {{ $promotions->links() }}
</main>
@endsection
