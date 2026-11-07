@extends(auth()->check() ? 'layouts.app' : 'layouts.storefront')
@section('title', 'Keranjang')
@section('breadcrumb', 'Keranjang')
@section('content')
@include('components.commerce-styles')
<div class="rk-commerce">
    @if ($errors->any())<div class="rk-commerce-errors" role="alert"><strong>Keranjang perlu diperiksa.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="rk-commerce-head"><div><div class="rk-commerce-eyebrow">Pesanan Anda</div><h1>Keranjang belanja</h1><p>Harga diambil dari database dan akan dihitung ulang oleh server saat checkout.</p></div><a class="rk-commerce-btn ghost" href="{{ auth()->check() ? route('customer.catalog') : route('storefront.menu') }}">← Kembali ke menu</a></div>
    @if (session('success'))<div class="rk-commerce-alert">{{ session('success') }}</div>@endif
    @if (empty($items))
        <section class="rk-commerce-panel rk-commerce-empty"><h3>Keranjang masih kosong</h3><p>Jelajahi menu dan tambahkan roti atau paket combo favoritmu.</p><a class="rk-commerce-btn" href="{{ route('storefront.menu') }}">Jelajahi menu</a></section>
    @else
        <section class="rk-commerce-panel"><div class="rk-commerce-table-wrap"><table class="rk-commerce-table"><thead><tr><th>Item</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th><th>Aksi</th></tr></thead><tbody>
            @foreach($items as $item)
                <tr><td><div class="rk-commerce-product">@if($item['image_path'])<img class="rk-commerce-thumb" src="{{ asset('storage/'.$item['image_path']) }}" alt="Foto {{ $item['name'] }}">@else<div class="rk-commerce-thumb">🥐</div>@endif<div><strong>{{ $item['name'] }}</strong><small>{{ strtoupper($item['type']) }} · {{ $item['sku'] }}</small>@if($item['quantity'] > $item['available_quantity'])<small style="color:#a23c26">Stok tersedia {{ $item['available_quantity'] }}.</small>@endif</div></div></td>
                <td>Rp {{ number_format($item['unit_price_cents'] / 100, 0, ',', '.') }}</td>
                <td><form class="rk-commerce-inline" method="POST" action="{{ route('customer.cart.items.update', ['type'=>$item['type'],'id'=>$item['id']]) }}">@csrf @method('PUT')<input class="rk-commerce-input" style="width:76px" type="number" name="quantity" min="1" max="99" value="{{ $item['quantity'] }}" aria-label="Jumlah {{ $item['name'] }}"><button class="rk-commerce-btn small" type="submit">Ubah</button></form></td>
                <td><strong>Rp {{ number_format($item['line_total_cents'] / 100, 0, ',', '.') }}</strong></td>
                <td><form method="POST" action="{{ route('customer.cart.items.destroy', ['type'=>$item['type'],'id'=>$item['id']]) }}">@csrf @method('DELETE')<button class="rk-commerce-btn danger small" type="submit">Hapus</button></form></td></tr>
            @endforeach
        </tbody></table></div>
        <div style="max-width:360px;margin:18px 0 0 auto"><div class="rk-commerce-total final"><span>Subtotal</span><span>Rp {{ number_format($subtotal_cents / 100, 0, ',', '.') }}</span></div><p class="rk-commerce-note">Stok diperiksa kembali saat pesanan dibuat.</p><a class="rk-commerce-btn" style="width:100%" href="{{ route('customer.checkout') }}">@auth Lanjut ke checkout @else Masuk untuk checkout @endauth</a>@guest<p class="rk-commerce-note">Keranjang disimpan di sesi browser ini. Setelah masuk atau daftar, kamu bisa lanjut checkout tanpa memilih ulang produk.</p>@endguest</div></section>
    @endif
</div>
@endsection
