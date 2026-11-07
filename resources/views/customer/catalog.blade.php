@extends('layouts.app')
@section('title', 'Katalog RotiKita')
@section('breadcrumb', 'Katalog')
@section('content')
@include('components.commerce-styles')
<div class="rk-commerce">
    @if ($errors->any())
        <div class="rk-commerce-errors" role="alert"><strong>Periksa kembali:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <div class="rk-commerce-head">
        <div><div class="rk-commerce-eyebrow">Fresh from the oven</div><h1>Katalog RotiKita Bakery</h1><p>Roti, cake, pastry, cookies, donat, dan snack. Harga, foto, dan stok dibaca langsung dari database.</p></div>
        <a class="rk-commerce-btn" href="{{ route('customer.cart') }}">Keranjang ({{ $cartCount }})</a>
    </div>

    <section class="rk-commerce-panel">
        <form class="rk-commerce-form-grid" method="GET" action="{{ route('customer.catalog') }}">
            <label class="rk-commerce-field full">Cari produk<input class="rk-commerce-input" type="search" name="q" value="{{ $search }}" placeholder="Nama, SKU, deskripsi, atau kategori"></label>
            <label class="rk-commerce-field">Kategori<select class="rk-commerce-select" name="category"><option value="">Semua kategori</option>@foreach($categories as $item)<option value="{{ $item }}" @selected($category === $item)>{{ $item }}</option>@endforeach</select></label>
            <div class="rk-commerce-inline" style="align-self:end"><button class="rk-commerce-btn" type="submit">Cari</button><a class="rk-commerce-btn ghost" href="{{ route('customer.catalog') }}">Reset</a></div>
        </form>
    </section>

    <div class="rk-commerce-section-title"><div><h2>Produk satuan</h2><p>{{ $products->total() }} produk ditemukan.</p></div></div>
    <div class="rk-commerce-grid">
        @forelse($products as $product)
            <article class="rk-commerce-card">
                <div class="rk-commerce-card-image">
                    @if($product->image_path)<img data-store-image src="{{ asset('storage/'.ltrim($product->image_path, '/')) }}" alt="Foto {{ $product->name }}" loading="lazy"><div data-store-image-fallback class="rk-commerce-no-image" hidden>Foto produk belum tersedia</div>
                    @else<div class="rk-commerce-no-image">Foto produk belum tersedia</div>@endif
                </div>
                <div class="rk-commerce-card-body">
                    <div class="rk-commerce-meta">{{ $product->category }} · {{ $product->sku }}</div>
                    <h3>{{ $product->name }}</h3>
                    <p>{{ $product->description ?: 'Produk pilihan RotiKita.' }}</p>
                    <div class="rk-commerce-inline" style="justify-content:space-between;margin-top:auto"><strong class="rk-commerce-price">Rp {{ number_format((float)$product->price, 0, ',', '.') }}</strong>
                        @if($product->stock_qty > 0)<span class="rk-commerce-pill {{ $product->stock_qty <= 5 ? 'orange' : 'green' }}">Stok {{ $product->stock_qty }}</span>@else<span class="rk-commerce-pill red">Habis</span>@endif
                    </div>
                    <form method="POST" action="{{ route('customer.cart.items.store') }}" class="rk-commerce-inline">
                        @csrf<input type="hidden" name="type" value="product"><input type="hidden" name="id" value="{{ $product->id }}">
                        <label class="rk-commerce-field" style="width:76px"><span>Qty</span><input class="rk-commerce-input" type="number" name="quantity" value="1" min="1" max="99" {{ $product->stock_qty < 1 ? 'disabled' : '' }}></label>
                        <button class="rk-commerce-btn" type="submit" {{ $product->stock_qty < 1 ? 'disabled' : '' }}>Tambah</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="rk-commerce-panel rk-commerce-empty"><h3>{{ $category !== '' ? 'Belum ada produk aktif di kategori '.$category : ($search !== '' ? 'Belum ada produk yang cocok' : 'Belum ada produk aktif') }}</h3><p>{{ $search !== '' ? 'Coba kata pencarian atau kategori lain.' : 'Produk aktif dari RotiKita Bakery akan tampil di sini.' }}</p></div>
        @endforelse
    </div>
    <div style="margin-top:18px">{{ $products->links() }}</div>

    <div class="rk-commerce-section-title"><div><h2>Paket combo</h2><p>Paket berisi beberapa produk dengan harga khusus.</p></div></div>
    <div class="rk-commerce-grid">
        @forelse($combos as $combo)
            <article class="rk-commerce-card">
                <div class="rk-commerce-card-image">
                    @if($combo->image_path)<img src="{{ asset('storage/'.$combo->image_path) }}" alt="Foto {{ $combo->name }}" loading="lazy">
                    @else<div class="rk-commerce-no-image">Paket combo RotiKita</div>@endif
                </div>
                <div class="rk-commerce-card-body">
                    <div class="rk-commerce-meta">Paket · {{ $combo->sku }}</div><h3>{{ $combo->name }}</h3>
                    <p>{{ $combo->description ?: 'Paket pilihan RotiKita.' }}</p>
                    <div class="rk-commerce-note">Komposisi: @forelse($combo->items as $component){{ $component->product?->name ?? 'Produk' }} × {{ $component->quantity }}@if(!$loop->last), @endif @empty belum diatur @endforelse</div>
                    <div class="rk-commerce-inline" style="justify-content:space-between;margin-top:auto"><strong class="rk-commerce-price">Rp {{ number_format((float)$combo->price, 0, ',', '.') }}</strong><span class="rk-commerce-pill">{{ $combo->items->count() }} komponen</span></div>
                    <form method="POST" action="{{ route('customer.cart.items.store') }}" class="rk-commerce-inline">
                        @csrf<input type="hidden" name="type" value="combo"><input type="hidden" name="id" value="{{ $combo->id }}">
                        <label class="rk-commerce-field" style="width:76px"><span>Qty</span><input class="rk-commerce-input" type="number" name="quantity" value="1" min="1" max="99" {{ $combo->items->isEmpty() ? 'disabled' : '' }}></label>
                        <button class="rk-commerce-btn" type="submit" {{ $combo->items->isEmpty() ? 'disabled' : '' }}>Tambah paket</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="rk-commerce-panel rk-commerce-empty"><h3>Belum ada paket combo aktif</h3><p>Admin dapat menambahkan paket combo setelah modul pengelolaan combo tersedia.</p></div>
        @endforelse
    </div>
</div>
@endsection
