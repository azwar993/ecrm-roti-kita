@extends('layouts.storefront')

@section('title', 'Roti Hangat Setiap Hari')

@section('content')
@php($heroProduct = $featuredProducts->first())
<main class="rks-main">
    <section class="rks-hero">
        <div class="rks-hero-inner">
            <div class="rks-hero-copy">
                <p class="rks-eyebrow"><span></span> Fresh from our oven</p>
                <h1>Roti hangat,<br><em>hari yang lebih</em> manis.</h1>
                <p class="rks-hero-description">Temukan roti, cake, pastry, cookies, dan camilan untuk menemani hari bersama RotiKita Bakery.</p>
                <div class="rks-hero-actions">
                    <a class="rks-btn rks-btn-primary rks-btn-large" href="{{ route('storefront.menu') }}">Jelajahi menu <span aria-hidden="true">→</span></a>
                    @guest
                        <a class="rks-btn rks-btn-outline rks-btn-large" href="{{ route('register') }}">Buat akun gratis</a>
                    @else
                        @if (auth()->user()->isCustomer())
                            <a class="rks-btn rks-btn-outline rks-btn-large" href="{{ route('customer.orders') }}">Pesanan saya</a>
                        @elseif (auth()->user()->isAdmin())
                            <a class="rks-btn rks-btn-outline rks-btn-large" href="{{ route('admin.orders') }}">Kelola pesanan</a>
                        @endif
                    @endguest
                </div>
                <div class="rks-hero-points"><span><b>✓</b> Menu berbasis stok nyata</span><span><b>✓</b> Pesan online lebih mudah</span></div>
            </div>
            <div class="rks-hero-visual" aria-label="Ilustrasi roti hangat">
                <div class="rks-hero-orbit rks-orbit-one"></div><div class="rks-hero-orbit rks-orbit-two"></div>
                <div class="rks-hero-sticker rks-sticker-top"><span>✳</span> Dibuat dengan hati</div>
                @if ($heroProduct && $heroProduct->image_path)
                    <div class="rks-hero-product-photo">
                        <img data-store-image src="{{ asset('storage/' . ltrim($heroProduct->image_path, '/')) }}" alt="Foto {{ $heroProduct->name }}" fetchpriority="high">
                        <span data-store-image-fallback class="rks-product-fallback" aria-hidden="true" hidden>🥐</span>
                        <div class="rks-hero-product-caption"><span>Favorit dari dapur kami</span><strong>{{ $heroProduct->name }}</strong><b>Rp {{ number_format((float) $heroProduct->price, 0, ',', '.') }}</b></div>
                    </div>
                @else
                    <div class="rks-hero-bread" aria-hidden="true"><span class="rks-bread-emoji">🥐</span><span class="rks-bread-glow"></span></div>
                @endif
                <div class="rks-hero-sticker rks-sticker-bottom"><span class="rks-sticker-dot"></span> Freshly baked daily</div>
                <span class="rks-hero-spark rks-spark-a" aria-hidden="true">✦</span><span class="rks-hero-spark rks-spark-b" aria-hidden="true">✳</span>
            </div>
        </div>
        <div class="rks-hero-bottom"><span>ROTIKITA BAKERY & CAFE</span><span>BAIK UNTUK HARI INI, HANGAT DI SETIAP GIGITAN</span></div>
    </section>

    <section class="rks-section rks-category-section" aria-labelledby="rks-category-title">
        <div class="rks-section-heading rks-section-heading-centered"><p class="rks-eyebrow">Temukan favoritmu</p><h2 id="rks-category-title">Ada yang enak untuk <em>setiap suasana.</em></h2><p>Mulai dari roti manis sampai pilihan gurih, temukan yang cocok dengan harimu.</p></div>
        <div class="rks-category-grid">
            @foreach ($featuredCategories as $index => $categoryName)
                @php($categoryIcon = match ($categoryName) {'Roti Manis' => '🥖', 'Roti Tawar' => '🍞', 'Cake & Dessert' => '🎂', 'Cake & Tart' => '🎂', 'Pastry' => '🥐', 'Cookies' => '🍪', 'Donat' => '🍩', 'Snack Bakery' => '🥨', 'Snack Gurih' => '🥨', 'Minuman' => '🥤', default => '🥐'})
                <a class="rks-category-card {{ $index === 0 ? 'rks-category-sweet' : 'rks-category-savory' }}" href="{{ route('storefront.menu', ['category' => $categoryName]) }}">
                    <span class="rks-category-icon" aria-hidden="true">{{ $categoryIcon }}</span>
                    <span><strong>{{ $categoryName }}</strong><small>Lihat produk kategori ini</small></span>
                    <span class="rks-category-arrow" aria-hidden="true">↗</span>
                </a>
            @endforeach
            <a class="rks-category-card rks-category-combo" href="{{ route('storefront.menu', ['section' => 'combo']) }}"><span class="rks-category-icon" aria-hidden="true">🎁</span><span><strong>Paket combo</strong><small>Lebih seru bersama-sama</small></span><span class="rks-category-arrow" aria-hidden="true">↗</span></a>
        </div>
    </section>

    <section class="rks-section rks-featured-section" aria-labelledby="rks-featured-title" data-product-carousel>
        <div class="rks-section-heading rks-section-heading-split"><div><p class="rks-eyebrow">Pilihan dari dapur kami</p><h2 id="rks-featured-title">Pilihan Bakery <em>RotiKita.</em></h2><p>Temukan berbagai pilihan roti dan bakery favoritmu.</p></div><div class="rks-carousel-heading-actions"><div class="rks-carousel-controls" aria-label="Navigasi pilihan bakery"><button type="button" data-carousel-prev aria-label="Geser ke produk sebelumnya" disabled><span aria-hidden="true">←</span></button><button type="button" data-carousel-next aria-label="Geser ke produk berikutnya" disabled><span aria-hidden="true">→</span></button></div><a class="rks-text-link" href="{{ route('storefront.menu') }}">Lihat semua menu <span aria-hidden="true">→</span></a></div></div>
        <div class="rks-product-carousel-track" data-carousel-track role="region" aria-roledescription="carousel" aria-label="Produk aktif RotiKita Bakery" tabindex="0">
            @forelse ($featuredProducts as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @empty
                <div class="rks-empty-products"><span aria-hidden="true">🥐</span><h3>Belum ada produk tersedia</h3><p>Pilihan bakery akan tampil di sini setelah admin menambahkan foto aktual, menyetujui harga, mencatat stok, dan mengaktifkan produk.</p><a href="{{ route('storefront.menu') }}" class="rks-text-link">Buka katalog <span aria-hidden="true">→</span></a></div>
            @endforelse
        </div>
    </section>

    @if ($featuredCombos->isNotEmpty())
        <section class="rks-section rks-combo-section" aria-labelledby="rks-combo-title">
            <div class="rks-section-heading rks-section-heading-split"><div><p class="rks-eyebrow">Dibuat untuk berbagi</p><h2 id="rks-combo-title">Paket hemat, <em>momen lebih hangat.</em></h2></div><a class="rks-text-link" href="{{ route('storefront.menu', ['section' => 'combo']) }}">Lihat paket <span aria-hidden="true">→</span></a></div>
            <div class="rks-product-grid">
                @foreach ($featuredCombos as $combo)
                    @include('storefront.partials.combo-card', ['combo' => $combo])
                @endforeach
            </div>
        </section>
    @endif

    <section class="rks-section rks-promotion-section" id="promotions" aria-labelledby="rks-promotion-title">
        <div class="rks-section-heading rks-section-heading-split">
            <div><p class="rks-eyebrow">Kabar baik untukmu</p><h2 id="rks-promotion-title">Promo RotiKita <em>hari ini.</em></h2><p>Penawaran yang sedang aktif dari dapur kami.</p></div>
            <a class="rks-text-link" href="{{ route('storefront.promotions') }}">Semua promo <span aria-hidden="true">→</span></a>
        </div>
        @forelse ($promotions as $promotion)
            <article class="rks-promotion-card">
                <div><span class="rks-promotion-code">{{ $promotion->code }}</span><h3>{{ $promotion->name }}</h3><p>{{ $promotion->description ?: 'Gunakan kode promo ini saat checkout.' }}</p></div>
                <strong>{{ $promotion->discount_type === 'percentage' ? rtrim(rtrim(number_format((float) $promotion->discount_value, 2), '0'), '.').'%' : 'Rp '.number_format((float) $promotion->discount_value, 0, ',', '.') }}</strong>
            </article>
        @empty
            <div class="rks-content-empty"><h3>Belum ada promo aktif</h3><p>Promo RotiKita akan tampil di sini saat tersedia.</p></div>
        @endforelse
    </section>

    <section class="rks-section rks-article-section" aria-labelledby="rks-articles-title">
        <div class="rks-section-heading rks-section-heading-split"><div><p class="rks-eyebrow">Inspirasi dari dapur</p><h2 id="rks-articles-title">Cerita dari Dapur <em>RotiKita</em></h2><p>Tips, cerita proses, dan kabar terbaru dari bakery kami.</p></div><a class="rks-text-link" href="{{ route('articles.index') }}">Semua artikel <span aria-hidden="true">→</span></a></div>
        @if ($articles->isNotEmpty())
            <div class="rks-article-grid">
                @foreach ($articles as $article)
                    <article class="rks-article-card">
                        <a href="{{ route('articles.show', $article) }}" class="rks-article-cover">
                            @if ($article->cover_path)<img src="{{ asset('storage/'.$article->cover_path) }}" alt="" loading="lazy">@else<span aria-hidden="true">🥐</span>@endif
                        </a>
                        <div class="rks-article-card-copy"><time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->translatedFormat('d F Y') }}</time><h3><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h3><p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit($article->body, 135) }}</p><a class="rks-text-link" href="{{ route('articles.show', $article) }}">Baca selengkapnya <span aria-hidden="true">→</span></a></div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="rks-content-empty"><h3>Cerita baru sedang disiapkan</h3><p>Artikel RotiKita akan hadir di sini setelah diterbitkan.</p></div>
        @endif
    </section>

    <section class="rks-section rks-social-section" aria-labelledby="rks-social-title">
        <div class="rks-social-copy"><p class="rks-eyebrow">Tetap terhubung</p><h2 id="rks-social-title">Ikuti Keseruan <em>RotiKita</em></h2><p>Lihat kabar terbaru dan momen dari dapur RotiKita.</p></div>
        <div>
            @include('storefront.partials.social-links')
            @if (!filled(config('rotikita-contact.instagram')) && !filled(config('rotikita-contact.tiktok')) && !filled(config('rotikita-contact.facebook')) && !filled(config('rotikita-contact.youtube')))
                <p>Akun media sosial resmi belum dikonfigurasi.</p>
            @endif
        </div>
    </section>

    <section class="rks-cta-banner">
        <div class="rks-cta-mark" aria-hidden="true">R.</div><div><p class="rks-eyebrow">Lebih praktis bareng RotiKita</p><h2>Sudah tahu mau makan apa?</h2><p>Lihat pilihan kami, simpan favorit, dan pantau pesanan setelah masuk akun.</p></div><a class="rks-btn rks-btn-cream" href="{{ route('storefront.menu') }}">Pilih roti sekarang <span aria-hidden="true">→</span></a>
    </section>
</main>
@endsection
