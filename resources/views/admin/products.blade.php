@extends('layouts.app')
@section('title', 'Manajemen Produk')
@section('breadcrumb', 'Produk')
@section('content')
<div class="rk-page-heading rk-page-heading-v2">
    <div><p class="rk-eyebrow">Data operasional · MySQL</p><h1>Manajemen produk</h1><p>Data pada halaman ini diambil dari database. Foto produk diunggah oleh admin.</p></div>
    <div class="rk-heading-actions"><a class="rk-btn" href="{{ route('admin.products.create') }}">＋ Tambah produk</a></div>
</div>

<div class="rk-real-stats">
    <article class="rk-real-stat"><span>Total produk</span><strong>{{ number_format($totalProducts) }}</strong><small>Semua produk tersimpan</small></article>
    <article class="rk-real-stat"><span>Produk aktif</span><strong>{{ number_format($activeProducts) }}</strong><small>Tampil di katalog customer</small></article>
    <article class="rk-real-stat"><span>Stok menipis</span><strong>{{ number_format($lowStockProducts) }}</strong><small>Stok 1–5 unit</small></article>
    <article class="rk-real-stat"><span>Stok habis</span><strong>{{ number_format($outOfStockProducts) }}</strong><small>Produk aktif, stok 0</small></article>
</div>

<section class="rk-panel rk-panel-v2 rk-real-panel">
    <div class="rk-panel-heading"><div><h2>Daftar produk</h2><p>Pencarian dan filter menggunakan data database.</p></div></div>
    <form class="rk-real-toolbar" method="GET" action="{{ route('admin.products') }}">
        <label class="rk-search"><span>⌕</span><input name="q" type="search" value="{{ $search }}" placeholder="Cari nama, SKU, kategori…"></label>
        <select class="rk-select" name="category" aria-label="Filter kategori">
            <option value="">Semua kategori</option>
            @foreach($categories as $item)<option value="{{ $item }}" @selected($category === $item)>{{ $item }}</option>@endforeach
        </select>
        <select class="rk-select" name="status" aria-label="Filter status">
            <option value="all" @selected($status === 'all')>Semua status</option>
            <option value="active" @selected($status === 'active')>Aktif</option>
            <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
            <option value="low" @selected($status === 'low')>Stok menipis</option>
            <option value="out" @selected($status === 'out')>Stok habis</option>
        </select>
        <button class="rk-btn rk-btn-small" type="submit">Terapkan</button>
        <a class="rk-btn rk-btn-quiet rk-btn-small" href="{{ route('admin.products') }}">Reset</a>
    </form>

    <div class="rk-table-wrap">
        <table class="rk-table rk-data-table rk-real-table">
            <thead><tr><th>Produk</th><th>SKU</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse($products as $product)
                <tr>
                    <td><div class="rk-real-product-cell">
                        @if($product->image_path)
                            <img class="rk-real-thumb" src="{{ asset('storage/'.$product->image_path) }}" alt="Foto {{ $product->name }}" loading="lazy" onerror="this.hidden=true;this.nextElementSibling.hidden=false">
                            <div class="rk-real-thumb rk-real-thumb-empty" aria-label="Foto produk tidak tersedia" hidden>Foto</div>
                        @else
                            <div class="rk-real-thumb rk-real-thumb-empty" aria-label="Belum ada foto">Foto</div>
                        @endif
                        <div><strong>{{ $product->name }}</strong><small>{{ \Illuminate\Support\Str::limit($product->description ?? 'Tidak ada deskripsi.', 62) }}</small></div>
                    </div></td>
                    <td><span class="rk-mono">{{ $product->sku }}</span></td>
                    <td>{{ $product->category }}</td>
                    <td><strong>Rp {{ number_format((float) $product->price, 0, ',', '.') }}</strong>@if($product->price_status === 'proposed')<small class="rk-badge rk-badge-low">Harga usulan</small>@endif @if($product->unit)<small>per {{ $product->unit }}</small>@endif</td>
                    <td>{{ number_format($product->stock_qty) }}</td>
                    <td>
                        @if(!$product->is_active)<span class="rk-badge">Nonaktif</span>
                        @elseif($product->stock_qty === 0)<span class="rk-badge rk-badge-out">Habis</span>
                        @elseif($product->stock_qty <= 5)<span class="rk-badge rk-badge-low">Menipis</span>
                        @else<span class="rk-badge rk-badge-active">Aktif</span>@endif
                    </td>
                    <td><div class="rk-real-actions">
                        <a class="rk-table-action" href="{{ route('admin.products.show', $product) }}">Detail</a>
                        <a class="rk-table-action" href="{{ route('admin.products.edit', $product) }}">Edit</a>
                        @if($product->is_active)
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Nonaktifkan produk ini? Data dan foto tetap disimpan.');">
                                @csrf @method('DELETE')
                                <button class="rk-table-action rk-real-danger" type="submit">Nonaktifkan</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.products.active', $product) }}" onsubmit="return confirm('Aktifkan produk ini untuk pelanggan? Pastikan foto, harga, dan stok sudah benar.');">
                                @csrf @method('PATCH')
                                <button class="rk-table-action" type="submit">Aktifkan</button>
                            </form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="rk-real-empty"><strong>Belum ada produk.</strong><p>Tambahkan produk pertama dan unggah foto asli. Produk yang disimpan akan muncul di katalog customer.</p><a class="rk-btn rk-btn-small" href="{{ route('admin.products.create') }}">＋ Tambah produk</a></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="rk-real-pagination">{{ $products->links() }}</div>
</section>
@endsection
