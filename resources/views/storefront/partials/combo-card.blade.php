@php
    $comboHasComponents = $combo->items->isNotEmpty();
    $comboComponentsAvailable = $comboHasComponents && $combo->items->every(function ($component) {
        return $component->product && $component->product->is_active && (int) $component->product->stock_qty >= (int) $component->quantity;
    });
@endphp
<article class="rks-product-card rks-combo-card">
    <div class="rks-product-image">
        @if ($combo->image_path)
            <img data-store-image src="{{ asset('storage/' . ltrim($combo->image_path, '/')) }}" alt="Foto {{ $combo->name }}" loading="lazy">
        @endif
        <span data-store-image-fallback class="rks-product-fallback" aria-hidden="true" @if($combo->image_path) hidden @endif>🎁</span>
        <span class="rks-product-ribbon rks-combo-ribbon">Paket hemat</span>
    </div>
    <div class="rks-product-body">
        <div class="rks-product-meta"><span>Combo RotiKita</span><span aria-hidden="true">·</span><span>{{ $combo->sku }}</span></div>
        <h3>{{ $combo->name }}</h3>
        <p>{{ \Illuminate\Support\Str::limit($combo->description ?: 'Kombinasi favorit untuk dinikmati bersama.', 96) }}</p>
        <div class="rks-combo-contents">
            @foreach ($combo->items as $component)
                <span>{{ $component->product?->name ?? 'Produk' }} × {{ $component->quantity }}</span>
            @endforeach
        </div>
        <div class="rks-product-bottom"><strong class="rks-price">Rp {{ number_format((float) $combo->price, 0, ',', '.') }}</strong><span class="rks-stock {{ $comboComponentsAvailable ? 'is-available' : 'is-unavailable' }}">{{ $comboComponentsAvailable ? 'Tersedia' : 'Komponen habis' }}</span></div>
        <form class="rks-add-form" method="POST" action="{{ route('customer.cart.items.store') }}">
            @csrf
            <input type="hidden" name="type" value="combo">
            <input type="hidden" name="id" value="{{ $combo->id }}">
            <label class="rks-sr-only" for="combo-qty-{{ $combo->id }}">Jumlah {{ $combo->name }}</label>
            <input id="combo-qty-{{ $combo->id }}" class="rks-qty" type="number" name="quantity" value="1" min="1" max="99" @disabled(!$comboComponentsAvailable)>
            <button class="rks-btn rks-btn-add" type="submit" @disabled(!$comboComponentsAvailable)><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg> Tambah</button>
        </form>
    </div>
</article>
