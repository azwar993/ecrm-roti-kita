@extends('layouts.storefront')

@section('title', 'Roti Hangat Setiap Hari')

@section('content')
@php
    $heroProduct = $featuredProducts->first(fn ($product) => preg_match('/roti|bread|croissant|pastry|donat/i', $product->name) === 1) ?? $featuredProducts->first();
    $showcaseProducts = $featuredProducts->take(4);
    $promoVisual = $featuredProducts->first(fn ($product) => $product->id !== $heroProduct?->id) ?? $heroProduct;
    $specialPromotion = $promotions->first();
@endphp
<main class="rks-main rk-home-redesign">
    <section class="rk-home-hero" aria-labelledby="home-hero-title">
        <div class="rk-home-hero-copy">
            <p class="rk-home-kicker"><span></span> ROTIKITA BAKERY · FRESH EVERY DAY</p>
            <h1 id="home-hero-title">Roti Lezat,<br><em>Hidup Lebih Manis</em></h1>
            <p>Nikmati pilihan bakery berkualitas untuk menemani setiap momen spesial Anda.</p>
            <a class="rk-home-button" href="{{ route('storefront.menu') }}">Lihat Menu <span aria-hidden="true">→</span></a>
            <div class="rk-home-trust"><span>✦ Dipanggang segar setiap hari</span><span>✦ Dibuat dengan bahan pilihan</span></div>
        </div>
        <div class="rk-home-hero-photo" aria-label="Pilihan bakery unggulan RotiKita">
            @if($heroProduct?->displayImagePath())
                <img src="{{ asset('storage/'.$heroProduct->displayImagePath()) }}" alt="{{ $heroProduct->name }} dari RotiKita" fetchpriority="high">
            @else
                <div class="rk-home-photo-placeholder"><span>RotiKita</span><small>fresh from our oven</small></div>
            @endif
            <div class="rk-home-photo-note"><span>FAVORIT DAPUR KAMI</span><strong>{{ $heroProduct?->name ?? 'Roti hangat setiap hari' }}</strong>@if($heroProduct)<b>Rp {{ number_format((float) $heroProduct->price, 0, ',', '.') }}</b>@endif</div>
            <span class="rk-home-photo-stamp">BAKED<br>WITH LOVE</span>
        </div>
        <div class="rk-home-hero-index"><span>01</span><i></i><span>ROTIKITA BAKERY</span></div>
    </section>

    <section class="rk-home-categories" aria-label="Jelajahi kategori bakery">
        <span class="rk-home-category-label">Jelajahi menu</span>
        <div class="rk-home-category-pills">
            <a class="is-active" href="{{ route('storefront.menu') }}">Semua menu</a>
            @foreach($featuredCategories as $categoryName)
                <a href="{{ route('storefront.menu', ['category' => $categoryName]) }}">@if($categoryImages->has($categoryName))<img src="{{ asset('storage/'.$categoryImages->get($categoryName)) }}" alt="Produk {{ $categoryName }}" loading="lazy">@else<span class="rk-home-category-mark" aria-hidden="true">✳</span>@endif{{ $categoryName }}</a>
            @endforeach
            <a href="{{ route('storefront.menu', ['section' => 'combo']) }}">Paket combo</a>
        </div>
    </section>

    <section class="rk-home-showcase" aria-labelledby="home-featured-title">
        <div class="rk-home-section-head"><div><p class="rk-home-kicker">Pilihan Bakery · Yang baru keluar dari oven</p><h2 id="home-featured-title">Pilihan <em>unggulan.</em></h2></div><a href="{{ route('storefront.menu') }}">Lihat semua menu <span aria-hidden="true">→</span></a></div>
        <div class="rk-home-showcase-grid" data-product-carousel>
            <div class="rk-home-product-area">
            <div class="rk-home-product-grid" data-carousel-track tabindex="0" aria-label="Produk unggulan RotiKita">
                @forelse($showcaseProducts as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @empty
                    <div class="rk-home-empty"><strong>Menu unggulan sedang disiapkan</strong><span>Produk aktif akan muncul di area ini.</span></div>
                @endforelse
            </div>
            <div class="rk-home-carousel-controls" aria-label="Navigasi produk unggulan"><button type="button" data-carousel-prev aria-label="Produk sebelumnya">←</button><button type="button" data-carousel-next aria-label="Produk berikutnya">→</button></div>
            </div>
            <aside class="rk-home-promo-card">
                @if($promoVisual?->displayImagePath())
                    <img class="rk-home-promo-photo" src="{{ asset('storage/'.$promoVisual->displayImagePath()) }}" alt="Pilihan bakery RotiKita untuk menikmati promo" loading="lazy">
                @endif
                <p class="rk-home-kicker">SEDIKIT KEHANGATAN EKSTRA</p>
                @if($specialPromotion)
                    <span class="rk-home-promo-code">{{ $specialPromotion->code }}</span>
                    <h3>{{ trim(strtoupper($specialPromotion->name)) === trim(strtoupper($specialPromotion->code)) ? 'Manisnya momen bersama' : $specialPromotion->name }}</h3>
                    <p>{{ $specialPromotion->description ?: 'Gunakan kode ini saat checkout dan nikmati bakery favoritmu.' }}</p>
                    <strong class="rk-home-promo-value">{{ $specialPromotion->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $specialPromotion->discount_value, 2), '0'), '.').'%' : 'Rp '.number_format((float) $specialPromotion->discount_value, 0, ',', '.') }}<small> untukmu</small></strong>
                    <a href="{{ route('storefront.promotions') }}">Gunakan promo <span aria-hidden="true">↗</span></a>
                @else
                    <h3>Momen manis<br>menanti.</h3><p>Promo spesial RotiKita akan tampil di sini saat tersedia.</p><a href="{{ route('storefront.menu') }}">Temukan menu <span aria-hidden="true">→</span></a>
                @endif
            </aside>
        </div>
    </section>

    @if($featuredCombos->isNotEmpty())
        <section class="rk-home-combo" aria-labelledby="home-combo-title">
            <div class="rk-home-combo-copy"><p class="rk-home-kicker">LEBIH SERU DINIKMATI BERSAMA</p><h2 id="home-combo-title">Paket combo,<br><em>momen lebih hangat.</em></h2><p>Padukan favoritmu dalam satu paket istimewa.</p><a href="{{ route('storefront.menu', ['section' => 'combo']) }}">Jelajahi semua paket <span aria-hidden="true">→</span></a></div>
            <div class="rk-home-combo-list">
                @foreach($featuredCombos->take(2) as $combo)
                    @php($comboNormalPrice = $combo->items->sum(fn ($component) => (float) ($component->product?->price ?? 0) * (int) $component->quantity))
                    <article class="rk-home-combo-item">
                        @if($combo->image_path)<img src="{{ asset('storage/'.$combo->image_path) }}" alt="Paket lengkap {{ $combo->name }}" loading="lazy">@else<span class="rk-home-combo-placeholder" aria-hidden="true">Paket RotiKita</span>@endif
                        <div><small>PAKET ROTIKITA</small><h3>{{ $combo->name }}</h3>
                            @if($comboNormalPrice > (float) $combo->price)<span class="rk-home-combo-original">Nilai satuan Rp {{ number_format($comboNormalPrice, 0, ',', '.') }}</span>@endif
                            <strong>Rp {{ number_format((float) $combo->price, 0, ',', '.') }}</strong>
                            @if($comboNormalPrice > (float) $combo->price)<span class="rk-home-combo-saving">Hemat Rp {{ number_format($comboNormalPrice - (float) $combo->price, 0, ',', '.') }}</span>@endif
                            <a href="{{ route('storefront.menu', ['section' => 'combo']) }}" aria-label="Lihat paket {{ $combo->name }}">Lihat paket →</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if($articles->isNotEmpty())
        <section class="rk-home-stories" aria-labelledby="home-stories-title"><div class="rk-home-section-head"><div><p class="rk-home-kicker">CERITA DARI DAPUR</p><h2 id="home-stories-title">Sedikit cerita, <em>banyak rasa.</em></h2></div><a href="{{ route('articles.index') }}">Baca semua artikel <span aria-hidden="true">→</span></a></div><div class="rk-home-story-grid">@foreach($articles as $article)<a class="rk-home-story" href="{{ route('articles.show', $article) }}">@if($article->display_cover_path)<img src="{{ asset('storage/'.$article->display_cover_path) }}" alt="{{ $article->cover_alt }}" loading="lazy">@endif<div><time>{{ $article->published_at->translatedFormat('d F Y') }}</time><h3>{{ $article->title }}</h3><span>Baca selengkapnya →</span></div></a>@endforeach</div></section>
    @endif
</main>
@endsection
