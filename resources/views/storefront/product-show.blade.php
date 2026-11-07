@extends('layouts.storefront')

@section('title', $product->name.' — Menu Bakery')

@section('content')
<main class="rks-main rks-product-detail-main">
    <section class="rks-section rks-product-detail" aria-labelledby="product-title">
        <a class="rks-text-link" href="{{ route('storefront.menu') }}">← Kembali ke menu</a>
        <article class="rks-product-detail-card">
            <div class="rks-product-detail-photo">
                @if($product->image_path)
                    <img data-store-image src="{{ asset('storage/'.ltrim($product->image_path, '/')) }}" alt="Foto {{ $product->name }}">
                    <span data-store-image-fallback class="rks-product-fallback" aria-hidden="true" hidden>🥐</span>
                @else
                    <span class="rks-product-fallback" aria-hidden="true">🥐</span>
                    <span class="rks-detail-photo-note">Foto produk belum tersedia</span>
                @endif
            </div>
            <div class="rks-product-detail-copy">
                <p class="rks-eyebrow">{{ $product->category }}</p>
                <h1 id="product-title">{{ $product->name }}</h1>
                <p class="rks-product-detail-description">{{ $product->description ?: 'Pilihan bakery RotiKita yang dibuat dengan sepenuh hati.' }}</p>
                <p class="rks-product-detail-sku">SKU {{ $product->sku }}</p>
                <div class="rks-product-detail-price"><strong>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</strong><span class="rks-stock {{ (int) $product->stock_qty > 0 ? 'is-available' : 'is-unavailable' }}">{{ (int) $product->stock_qty > 0 ? 'Tersedia · '.number_format($product->stock_qty).' unit' : 'Stok habis' }}</span></div>
                <form class="rks-add-form rks-detail-add-form" method="POST" action="{{ route('customer.cart.items.store') }}">
                    @csrf
                    <input type="hidden" name="type" value="product">
                    <input type="hidden" name="id" value="{{ $product->id }}">
                    <label class="rks-sr-only" for="product-detail-qty">Jumlah {{ $product->name }}</label>
                    <input id="product-detail-qty" class="rks-qty" type="number" name="quantity" value="1" min="1" max="99" @disabled((int) $product->stock_qty < 1)>
                    <button class="rks-btn rks-btn-add" type="submit" @disabled((int) $product->stock_qty < 1)>Tambah ke keranjang</button>
                </form>
            </div>
        </article>
    </section>
</main>
@endsection
