@extends('layouts.app')
@section('title', 'Detail Produk')
@section('breadcrumb', 'Produk / Detail')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Data produk dari database</p><h1>{{ $product->name }}</h1><p>SKU <span class="rk-mono">{{ $product->sku }}</span></p></div><div class="rk-heading-actions"><a class="rk-btn rk-btn-quiet" href="{{ route('admin.products') }}">Kembali</a><a class="rk-btn" href="{{ route('admin.products.edit', $product) }}">Edit produk</a></div></div>
<section class="rk-panel rk-panel-v2 rk-product-detail-admin">
    <div class="rk-product-admin-photo">
        @if($product->image_path)
            <img src="{{ asset('storage/'.ltrim($product->image_path, '/')) }}" alt="Foto {{ $product->name }}" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
            <div class="rk-real-thumb-empty" hidden>Foto tidak tersedia</div>
        @else
            <div class="rk-real-thumb-empty">Foto produk belum tersedia</div>
        @endif
    </div>
    <div class="rk-product-admin-facts">
        <p><span>Kategori</span><strong>{{ $product->category }}</strong></p>
        <p><span>Harga jual</span><strong>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</strong></p>
        <p><span>Stok aktual</span><strong>{{ number_format($product->stock_qty) }}</strong></p>
        <p><span>Status</span><strong>{{ $product->is_active ? 'Aktif' : 'Draft / nonaktif' }}</strong></p>
        <p><span>Riwayat perubahan stok</span><strong>{{ number_format($product->stock_movements_count) }}</strong></p>
        <p><span>Komposisi combo</span><strong>{{ number_format($product->combo_items_count) }}</strong></p>
        <p class="rk-product-admin-description"><span>Deskripsi</span><strong>{{ $product->description ?: 'Deskripsi belum diisi.' }}</strong></p>
    </div>
</section>
@endsection
