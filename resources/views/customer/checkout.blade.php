@extends('layouts.app')

@section('title', 'Checkout')
@section('breadcrumb', 'Checkout')

@section('content')
@include('components.commerce-styles')

@php
    $selectedOrderType = old('order_type', 'takeaway');
    $selectedPaymentMethod = old('payment_method', 'cash');
@endphp

<div class="rk-commerce rk-checkout">
    @if ($errors->any())
        <div class="rk-commerce-errors" role="alert">
            <strong>Checkout belum bisa diproses.</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rk-commerce-head">
        <div>
            <div class="rk-commerce-eyebrow">Satu langkah lagi</div>
            <h1>Checkout</h1>
            <p>Pilih jenis pesanan dan metode pembayaran. Harga serta stok diperiksa kembali oleh server saat pesanan disimpan.</p>
        </div>
        <a class="rk-commerce-btn ghost" href="{{ route('customer.cart') }}">← Ubah keranjang</a>
    </div>

    <form method="POST" action="{{ route('customer.checkout.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="rk-commerce-grid two rk-checkout-layout">
            <div>
                <section class="rk-commerce-panel">
                    <h2 class="rk-checkout-section-heading">Detail pesanan</h2>

                    <div class="rk-commerce-form-grid">
                        <label class="rk-commerce-field">
                            <span>Pilih layanan</span>
                            <select class="rk-commerce-select" name="order_type" id="order-type" required>
                                <option value="takeaway" @selected($selectedOrderType === 'takeaway')>Takeaway — dibawa pulang</option>
                                <option value="dine_in" @selected($selectedOrderType === 'dine_in')>Dine-in — makan di tempat</option>
                            </select>
                        </label>

                        <section class="rk-commerce-note full" id="cash-payment-instructions" @if($selectedPaymentMethod !== 'cash') hidden @endif>
                            <strong>Tunai di kasir</strong><p>Pembayaran tunai dilakukan di kasir. Nominal pesanan: <strong data-checkout-total>Rp {{ number_format($checkout_total_cents / 100, 0, ',', '.') }}</strong>.</p>
                        </section>
                        <section class="rk-commerce-note full" id="qris-payment-instructions" @if($selectedPaymentMethod !== 'qris') hidden @endif>
                            <strong>Petunjuk QRIS</strong>
                            @if($qrisConfigured)
                                <p>Nominal pembayaran: <strong data-checkout-total>Rp {{ number_format($checkout_total_cents / 100, 0, ',', '.') }}</strong>. QRIS statis tidak mengisi nominal otomatis; masukkan nominal ini pada aplikasi pembayaran.</p>
                                <img src="{{ $qrisImageUrl }}" alt="QRIS resmi merchant RotiKita" style="display:block;max-width:300px;width:100%;height:auto;margin:12px auto">
                            @else
                                <p>QRIS resmi RotiKita belum dikonfigurasi. Silakan pilih tunai atau metode lain yang tersedia.</p>
                            @endif
                            <small>Scan QRIS, unggah bukti setelah pesanan dibuat, dan tunggu verifikasi admin.</small>
                        </section>
                        <section class="rk-commerce-note full" id="bank-payment-instructions" @if($selectedPaymentMethod !== 'bank_transfer') hidden @endif>
                            <strong>Petunjuk transfer bank</strong>
                            @if($bankDetails['bank_account_number'] && $bankDetails['bank_name'] && $bankDetails['bank_account_name'])
                                <p>{{ $bankDetails['bank_name'] }} · {{ $bankDetails['bank_account_number'] }} a.n. {{ $bankDetails['bank_account_name'] }}</p>
                            @else
                                <p>Informasi rekening RotiKita belum dikonfigurasi. Silakan pilih QRIS (jika tersedia) atau tunai.</p>
                            @endif
                            <small>Transfer tepat sebesar <strong data-checkout-total>Rp {{ number_format($checkout_total_cents / 100, 0, ',', '.') }}</strong>, lalu unggah bukti setelah pesanan dibuat.</small>
                        </section>

                        <label class="rk-commerce-field">
                            <span>Metode pembayaran</span>
                            <select class="rk-commerce-select" name="payment_method" id="payment-method" required>
                                <option value="cash" @selected($selectedPaymentMethod === 'cash')>Tunai di kasir</option>
                                <option value="bank_transfer" @selected($selectedPaymentMethod === 'bank_transfer')>Transfer bank</option>
                                <option value="qris" @selected($selectedPaymentMethod === 'qris')>QRIS</option>
                            </select>
                            <small id="payment-guidance" aria-live="polite">
                                @if ($selectedPaymentMethod === 'cash')
                                    Pembayaran tunai dilakukan di kasir.
                                @else
                                    Pembayaran perlu diverifikasi admin. Unggah bukti pembayaran jika tersedia.
                                @endif
                            </small>
                        </label>

                        <label class="rk-commerce-field" id="table-number-wrap" @if ($selectedOrderType !== 'dine_in') hidden @endif>
                            <span>Nomor meja</span>
                            <input
                                class="rk-commerce-input"
                                type="text"
                                id="table-number"
                                name="table_number"
                                value="{{ old('table_number') }}"
                                maxlength="30"
                                placeholder="Contoh: A3"
                                aria-describedby="table-number-help"
                                @if ($selectedOrderType === 'dine_in') required @endif
                            >
                            <small id="table-number-help">Wajib diisi untuk dine-in.</small>
                        </label>

                        <label class="rk-commerce-field">
                            <span>Nomor telepon <span class="rk-checkout-optional">(opsional)</span></span>
                            <input
                                class="rk-commerce-input"
                                type="tel"
                                name="customer_phone"
                                value="{{ old('customer_phone', auth()->user()->customer?->phone) }}"
                                maxlength="30"
                                placeholder="Nomor yang bisa dihubungi"
                                autocomplete="tel"
                            >
                        </label>

                        <label class="rk-commerce-field full">
                            <span>Kode promosi <span class="rk-checkout-optional">(opsional)</span></span>
                            <input
                                class="rk-commerce-input"
                                type="text"
                                name="promotion_code"
                                value="{{ old('promotion_code') }}"
                                maxlength="50"
                                placeholder="Masukkan kode promo yang aktif"
                                autocomplete="off"
                            >
                            <button class="rk-commerce-btn ghost small" type="button" id="apply-promotion">Terapkan kode</button><small id="promotion-feedback" aria-live="polite">Gunakan Terapkan kode untuk melihat total setelah diskon.</small>
                        </label>

                        <label class="rk-commerce-field full">
                            <span>Catatan pesanan <span class="rk-checkout-optional">(opsional)</span></span>
                            <textarea
                                class="rk-commerce-textarea"
                                name="notes"
                                maxlength="2000"
                                placeholder="Catatan untuk pesanan..."
                                rows="4"
                            >{{ old('notes') }}</textarea>
                        </label>
                    </div>
                </section>
            </div>

            <aside class="rk-commerce-panel rk-commerce-summary rk-checkout-summary">
                <h2 class="rk-checkout-section-heading">Ringkasan pesanan</h2>

                <div class="rk-checkout-items">
                    @foreach ($items as $item)
                        <div class="rk-commerce-total rk-checkout-line-item">
                            <span>{{ $item['name'] }} <span class="rk-checkout-quantity">× {{ $item['quantity'] }}</span></span>
                            <strong>Rp {{ number_format($item['line_total_cents'] / 100, 0, ',', '.') }}</strong>
                        </div>
                    @endforeach
                </div>

                <div class="rk-commerce-total rk-checkout-subtotal">
                    <span>Subtotal</span>
                    <strong>Rp {{ number_format($subtotal_cents / 100, 0, ',', '.') }}</strong>
                </div>

                <div class="rk-commerce-total"><span>Diskon promo</span><strong data-checkout-discount>Rp {{ number_format(($subtotal_cents - $checkout_total_cents) / 100, 0, ',', '.') }}</strong></div><div class="rk-commerce-total final"><span>Total pembayaran</span><strong data-checkout-total>Rp {{ number_format($checkout_total_cents / 100, 0, ',', '.') }}</strong></div>
                <div class="rk-commerce-note rk-checkout-summary-note">
                    Total pembayaran dihitung ulang di server. Promo yang diterapkan di atas sudah dimasukkan ke nominal ini. Stok diperiksa kembali saat pesanan disimpan.
                </div>

                <button class="rk-commerce-btn rk-checkout-submit" type="submit">Buat pesanan</button>
                <p class="rk-checkout-secure-note">Dengan membuat pesanan, stok akan direservasi sesuai jumlah yang dipesan.</p>
            </aside>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderType = document.getElementById('order-type');
    const tableWrap = document.getElementById('table-number-wrap');
    const tableInput = document.getElementById('table-number');
    const paymentMethod = document.getElementById('payment-method');
    const qrisInstructions = document.getElementById('qris-payment-instructions');
    const bankInstructions = document.getElementById('bank-payment-instructions');
    const paymentGuidance = document.getElementById('payment-guidance');
    const applyPromotion = document.getElementById('apply-promotion');
    const promotionInput = document.querySelector('[name=promotion_code]');
    const promotionFeedback = document.getElementById('promotion-feedback');
    const csrfToken = document.querySelector('meta[name=csrf-token]')?.content;

    function syncTableField() {
        const isDineIn = orderType && orderType.value === 'dine_in';

        if (tableWrap) tableWrap.hidden = !isDineIn;
        if (tableInput) {
            tableInput.required = isDineIn;
            if (!isDineIn) tableInput.value = '';
        }
    }

    function syncPaymentField() {
        const method = paymentMethod ? paymentMethod.value : 'cash';
        const needsProof = method === 'bank_transfer' || method === 'qris';
        const cashInstructions = document.getElementById('cash-payment-instructions');
        if (cashInstructions) cashInstructions.hidden = method !== 'cash';
        if (qrisInstructions) qrisInstructions.hidden = method !== 'qris';
        if (bankInstructions) bankInstructions.hidden = method !== 'bank_transfer';

        if (paymentGuidance) {
            paymentGuidance.textContent = needsProof
                ? 'Pembayaran perlu diverifikasi admin. Bukti dapat diunggah setelah pesanan dibuat.'
                : 'Pembayaran tunai dilakukan di kasir.';
        }
    }

    if (orderType) orderType.addEventListener('change', syncTableField);
    if (paymentMethod) paymentMethod.addEventListener('change', syncPaymentField);

    if (applyPromotion && promotionInput) applyPromotion.addEventListener('click', async function () {
        promotionFeedback.textContent = 'Memeriksa kode promo…';
        try {
            const response = await fetch('{{ route("customer.checkout.quote") }}', { method: 'POST', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify({ promotion_code: promotionInput.value }) });
            const data = await response.json();
            if (!response.ok) throw new Error(data.errors?.promotion_code?.[0] || 'Kode promo tidak dapat digunakan.');
            promotionInput.value = data.code;
            document.querySelectorAll('[data-checkout-total]').forEach(el => el.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(data.total)));
            document.querySelectorAll('[data-checkout-discount]').forEach(el => el.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.discount_cents / 100));
            promotionFeedback.textContent = 'Kode ' + data.code + ' diterapkan. Total dihitung ulang oleh server.';
        } catch (error) { promotionFeedback.textContent = error.message; }
    });

    syncTableField();
    syncPaymentField();
});
</script>
@endsection
