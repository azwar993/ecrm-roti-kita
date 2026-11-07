@extends('layouts.storefront')

@section('title', 'Katalog RotiKita Bakery')

@section('content')
<main class="rks-main rks-menu-main">
    <section class="rks-menu-hero">
        <p class="rks-eyebrow"><span></span> RotiKita Bakery</p>
        <h1>Cari teman <em>harimu.</em></h1>
        <p>Jelajahi produk yang tersedia. Tambahkan ke keranjang kapan saja, lalu masuk atau daftar saat siap checkout.</p>
    </section>

    <section class="rks-section rks-menu-section">
        <form class="rks-menu-filters" method="GET" action="{{ route('storefront.menu') }}">
            @if (request('section') === 'combo')<input type="hidden" name="section" value="combo">@endif
            <label class="rks-menu-search"><span class="rks-sr-only">Cari menu</span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.7"/><path d="m16 16 4.5 4.5"/></svg><input type="search" name="q" value="{{ $search }}" placeholder="Cari nama roti, kategori, atau SKU..."></label>
            <label class="rks-filter-label"><span class="rks-sr-only">Filter kategori</span><select name="category"><option value="">Semua kategori</option>@foreach ($categories as $item)<option value="{{ $item }}" @selected($category === $item)>{{ $item }}</option>@endforeach</select></label>
            <label class="rks-filter-label"><span class="rks-sr-only">Urutkan produk</span><select name="sort"><option value="newest" @selected(request('sort', 'newest') === 'newest')>Terbaru</option><option value="price_asc" @selected(request('sort') === 'price_asc')>Harga terendah</option><option value="price_desc" @selected(request('sort') === 'price_desc')>Harga tertinggi</option></select></label>
            <button type="submit" class="rks-btn rks-btn-primary">Cari menu</button>
            @if ($search !== '' || $category !== '')<a class="rks-btn rks-btn-quiet" href="{{ route('storefront.menu', request('section') === 'combo' ? ['section' => 'combo'] : []) }}">Reset</a>@endif
        </form>

        @if (request('section') !== 'combo')
            <div class="rks-section-heading rks-menu-heading"><div><p class="rks-eyebrow">Fresh from the oven</p><h2>Ragam bakery <em>pilihan.</em></h2><p>{{ $products->total() }} produk ditemukan. Harga dan stok mengikuti data terbaru.</p></div></div>
            <div class="rks-product-grid">
                @forelse ($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @empty
                    @if($category !== '')
                        <div class="rks-empty-products"><span aria-hidden="true">🔎</span><h3>Belum ada produk aktif di kategori {{ $category }}</h3><p>Produk dari kategori ini akan tampil saat tersedia dan aktif.</p><a href="{{ route('storefront.menu') }}" class="rks-text-link">Lihat semua kategori <span aria-hidden="true">→</span></a></div>
                    @elseif($search !== '')
                        <div class="rks-empty-products"><span aria-hidden="true">🔎</span><h3>Belum ada produk yang cocok</h3><p>Coba kata pencarian atau kategori lain, ya.</p><a href="{{ route('storefront.menu') }}" class="rks-text-link">Reset pencarian <span aria-hidden="true">→</span></a></div>
                    @else
                        <div class="rks-empty-products"><span aria-hidden="true">🥐</span><h3>Belum ada produk aktif</h3><p>Produk RotiKita Bakery akan tampil di sini saat tersedia dan aktif.</p><a href="{{ route('storefront.menu') }}" class="rks-text-link">Lihat kategori <span aria-hidden="true">→</span></a></div>
                    @endif
                @endforelse
            </div>
            <div class="rks-menu-pagination">{{ $products->links() }}</div>
        @endif

        @if (request('section') === 'combo' || $combos->isNotEmpty())
            <div class="rks-section-heading rks-menu-heading rks-combo-menu-heading"><div><p class="rks-eyebrow">Paket untuk dinikmati bersama</p><h2>Paket combo <em>lebih hemat.</em></h2><p>Komposisi dan ketersediaan komponen diperiksa kembali saat ditambahkan atau checkout.</p></div></div>
            @if ($combos->isNotEmpty())
                <div class="rks-product-grid">
                    @foreach ($combos as $combo)
                        @include('storefront.partials.combo-card', ['combo' => $combo])
                    @endforeach
                </div>
            @elseif (request('section') === 'combo')
                <div class="rks-empty-products"><span aria-hidden="true">🎁</span><h3>Belum ada paket combo aktif</h3><p>Paket akan ditampilkan setelah admin mengatur combo yang tersedia.</p></div>
            @endif
        @endif
    </section>
</main>
@endsection
