<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Combo;
use App\Models\ComboItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\OrderCreated;
use App\Services\PromotionDiscountCalculator;
use App\Services\StoreCartService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request, StoreCartService $cartService, PromotionDiscountCalculator $discountCalculator): View|RedirectResponse
    {
        $cart = $cartService->build($request);
        if (empty($cart['items'])) {
            return redirect()->route('customer.catalog')->with('error', 'Keranjang masih kosong.');
        }

        $settings = PaymentSetting::query()->whereKey(1)->first();
        $qrisImage = $this->availablePublicImage($settings?->qris_image)
            ?? $this->availablePublicImage(config('rotikita-payment.qris_image'));
        $qrisConfigured = $qrisImage !== null;
        $qrisImageUrl = $qrisConfigured ? Storage::disk('public')->url($qrisImage) : null;
        $bankDetails = $settings ? [
            'bank_name' => $settings->bank_name,
            'bank_account_name' => $settings->bank_account_name,
            'bank_account_number' => $settings->bank_account_number,
        ] : config('rotikita-payment');
        $checkoutTotalCents = (int) $cart['subtotal_cents'];
        $previewCode = strtoupper(trim((string) old('promotion_code', '')));
        if ($previewCode !== '') {
            $promotion = Promotion::query()->where('code', $previewCode)->first();
            if ($promotion?->isCurrentlyValid()
                && $checkoutTotalCents >= $cartService->toCents($promotion->min_order_amount)
                && ($promotion->usage_limit === null || $promotion->orders()->count() < $promotion->usage_limit)) {
                $discount = $discountCalculator->discountCents($promotion, $checkoutTotalCents, $cartService);
                $checkoutTotalCents = max(0, $checkoutTotalCents - $discount);
            }
        }

        return view('customer.checkout', $cart + [
            'qrisImage' => $qrisImage,
            'qrisConfigured' => $qrisConfigured,
            'qrisImageUrl' => $qrisImageUrl,
            'bankDetails' => $bankDetails,
            'checkout_total_cents' => $checkoutTotalCents,
        ]);
    }

    public function quotePromotion(Request $request, StoreCartService $cartService, PromotionDiscountCalculator $discountCalculator): JsonResponse
    {
        $data = $request->validate(['promotion_code' => ['required', 'string', 'max:50']]);
        $cart = $cartService->build($request);
        if ($cart['items'] === []) {
            throw ValidationException::withMessages(['promotion_code' => 'Keranjang kosong.']);
        }
        $code = strtoupper(trim($data['promotion_code']));
        $promotion = Promotion::query()->where('code', $code)->first();
        if (! $promotion || ! $promotion->isCurrentlyValid()) {
            throw ValidationException::withMessages(['promotion_code' => 'Kode promo tidak ditemukan, tidak aktif, atau di luar periode.']);
        }
        $subtotal = (int) $cart['subtotal_cents'];
        if ($subtotal < $cartService->toCents($promotion->min_order_amount)) {
            throw ValidationException::withMessages(['promotion_code' => 'Subtotal belum memenuhi minimum belanja untuk promo ini.']);
        }
        if ($promotion->usage_limit !== null && $promotion->orders()->count() >= (int) $promotion->usage_limit) {
            throw ValidationException::withMessages(['promotion_code' => 'Kuota penggunaan promo sudah habis.']);
        }
        $discount = $discountCalculator->discountCents($promotion, $subtotal, $cartService);

        return response()->json([
            'code' => $promotion->code,
            'subtotal_cents' => $subtotal,
            'discount_cents' => $discount,
            'total_cents' => max(0, $subtotal - $discount),
            'total' => $cartService->fromCents(max(0, $subtotal - $discount)),
        ]);
    }

    public function store(Request $request, StoreCartService $cartService, PromotionDiscountCalculator $discountCalculator): RedirectResponse
    {
        $data = $request->validate([
            'order_type' => ['required', Rule::in([Order::TYPE_DINE_IN, Order::TYPE_TAKEAWAY])],
            'table_number' => ['nullable', 'required_if:order_type,dine_in', 'string', 'max:30'],
            'payment_method' => ['required', Rule::in([Payment::METHOD_CASH, Payment::METHOD_BANK_TRANSFER, Payment::METHOD_QRIS])],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'promotion_code' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_proof' => ['prohibited'],
        ], [
            'table_number.required_if' => 'Nomor meja wajib diisi untuk pesanan dine-in.',
            'payment_proof.prohibited' => 'Bukti pembayaran dapat diunggah setelah pesanan dibuat.',
        ]);

        $settings = PaymentSetting::query()->whereKey(1)->first();
        if ($data['payment_method'] === Payment::METHOD_QRIS) {
            $image = $this->availablePublicImage($settings?->qris_image)
                ?? $this->availablePublicImage(config('rotikita-payment.qris_image'));
            if ($image === null) {
                throw ValidationException::withMessages(['payment_method' => 'QRIS RotiKita belum dikonfigurasi. Silakan pilih metode pembayaran lain.']);
            }
        }
        $bankDetails = $settings ? [
            'bank_name' => $settings->bank_name,
            'bank_account_name' => $settings->bank_account_name,
            'bank_account_number' => $settings->bank_account_number,
        ] : config('rotikita-payment');
        if ($data['payment_method'] === Payment::METHOD_BANK_TRANSFER
            && (! filled($bankDetails['bank_name'] ?? null)
                || ! filled($bankDetails['bank_account_name'] ?? null)
                || ! filled($bankDetails['bank_account_number'] ?? null))) {
            throw ValidationException::withMessages(['payment_method' => 'Rekening transfer RotiKita belum dikonfigurasi. Silakan pilih metode pembayaran lain.']);
        }
        $paymentDetails = match ($data['payment_method']) {
            Payment::METHOD_QRIS => ['qris_image' => $image, 'instruction' => 'Scan QRIS resmi RotiKita dan bayar sesuai total tagihan.'],
            Payment::METHOD_BANK_TRANSFER => ['bank_name' => $bankDetails['bank_name'], 'bank_account_name' => $bankDetails['bank_account_name'], 'bank_account_number' => $bankDetails['bank_account_number'], 'instruction' => 'Transfer sesuai total tagihan dan unggah bukti pembayaran.'],
            default => ['instruction' => 'Pembayaran tunai dilakukan di kasir.'],
        };

        $cart = $request->session()->get(StoreCartService::SESSION_KEY, []);
        if (! is_array($cart) || $cart === []) {
            return redirect()->route('customer.catalog')->with('error', 'Keranjang masih kosong.');
        }

        $order = DB::transaction(function () use ($request, $data, $cart, $cartService, $paymentDetails, $discountCalculator): Order {
            $user = $request->user();
            $customer = Customer::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $customer) {
                throw ValidationException::withMessages([
                    'customer' => 'Profil pelanggan belum tersedia. Lengkapi profil atau hubungi admin sebelum memesan.',
                ]);
            }

            $parsedCart = [];
            $productIds = [];
            $comboIds = [];
            foreach ($cart as $key => $quantity) {
                if (! preg_match('/^(product|combo)_(\d+)$/', (string) $key, $match)) {
                    continue;
                }
                $quantity = filter_var($quantity, FILTER_VALIDATE_INT);
                if ($quantity === false || $quantity < 1 || $quantity > 99) {
                    throw ValidationException::withMessages(['cart' => 'Jumlah item dalam keranjang tidak valid. Perbarui keranjang dan coba lagi.']);
                }
                $type = $match[1];
                $id = (int) $match[2];
                $parsedCart[] = ['type' => $type, 'id' => $id, 'quantity' => $quantity];
                if ($type === 'product') {
                    $productIds[] = $id;
                } else {
                    $comboIds[] = $id;
                }
            }
            if ($parsedCart === []) {
                throw ValidationException::withMessages(['cart' => 'Keranjang tidak berisi item yang valid.']);
            }

            /** @var EloquentCollection<int, Combo> $combos */
            $combos = Combo::query()->whereIn('id', array_values(array_unique($comboIds)))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $comboItems = ComboItem::query()
                ->whereIn('combo_id', array_values(array_unique($comboIds)))
                ->orderBy('product_id')->lockForUpdate()->get()->groupBy('combo_id');

            foreach ($comboItems as $items) {
                foreach ($items as $comboItem) {
                    $productIds[] = (int) $comboItem->product_id;
                }
            }
            $productIds = array_values(array_unique($productIds));
            sort($productIds);
            $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

            $lineDefinitions = [];
            $productDemand = [];
            $subtotalCents = 0;
            $now = now();

            foreach ($parsedCart as $cartLine) {
                $quantity = (int) $cartLine['quantity'];
                if ($cartLine['type'] === 'product') {
                    $product = $products->get($cartLine['id']);
                    if (! $product || ! $product->is_active) {
                        throw ValidationException::withMessages(['cart' => 'Ada produk yang sudah tidak aktif. Periksa kembali keranjang.']);
                    }
                    $unitCents = $cartService->toCents($product->price);
                    $lineCents = $unitCents * $quantity;
                    $lineDefinitions[] = [
                        'type' => 'product', 'model' => $product, 'quantity' => $quantity,
                        'unit_cents' => $unitCents, 'line_cents' => $lineCents,
                    ];
                    $productDemand[$product->id] = ($productDemand[$product->id] ?? 0) + $quantity;
                    $subtotalCents += $lineCents;

                    continue;
                }

                $combo = $combos->get($cartLine['id']);
                if (! $combo || ! $combo->is_active
                    || ($combo->starts_at && $combo->starts_at->isFuture())
                    || ($combo->ends_at && $combo->ends_at->isPast())) {
                    throw ValidationException::withMessages(['cart' => 'Ada paket combo yang tidak aktif atau sudah di luar periode penjualan.']);
                }
                $components = $comboItems->get($combo->id, collect());
                if ($components->isEmpty()) {
                    throw ValidationException::withMessages(['cart' => "Paket {$combo->name} belum memiliki komposisi produk."]);
                }
                foreach ($components as $component) {
                    $componentProduct = $products->get($component->product_id);
                    if (! $componentProduct || ! $componentProduct->is_active) {
                        throw ValidationException::withMessages(['cart' => "Komponen paket {$combo->name} sudah tidak tersedia."]);
                    }
                    $needed = (int) $component->quantity * $quantity;
                    $productDemand[$componentProduct->id] = ($productDemand[$componentProduct->id] ?? 0) + $needed;
                }
                $unitCents = $cartService->toCents($combo->price);
                $lineCents = $unitCents * $quantity;
                $lineDefinitions[] = [
                    'type' => 'combo', 'model' => $combo, 'quantity' => $quantity,
                    'unit_cents' => $unitCents, 'line_cents' => $lineCents,
                ];
                $subtotalCents += $lineCents;
            }

            foreach ($productDemand as $productId => $needed) {
                $product = $products->get($productId);
                if (! $product || (int) $product->stock_qty < $needed) {
                    $name = $product?->name ?? 'Produk';
                    $available = $product ? (int) $product->stock_qty : 0;
                    throw ValidationException::withMessages(['cart' => "Stok {$name} tidak cukup. Tersedia {$available}, dibutuhkan {$needed}. Sesuaikan keranjang."]);
                }
            }

            $promotion = null;
            $discountCents = 0;
            $promotionCode = strtoupper(trim((string) ($data['promotion_code'] ?? '')));
            if ($promotionCode !== '') {
                $promotion = Promotion::query()->where('code', $promotionCode)->lockForUpdate()->first();
                if (! $promotion || ! $promotion->isCurrentlyValid()) {
                    throw ValidationException::withMessages(['promotion_code' => 'Kode promo tidak ditemukan, tidak aktif, atau di luar periode.']);
                }
                if ($subtotalCents < $cartService->toCents($promotion->min_order_amount)) {
                    throw ValidationException::withMessages(['promotion_code' => 'Subtotal belum memenuhi minimum belanja untuk promo ini.']);
                }
                if ($promotion->usage_limit !== null && $promotion->orders()->count() >= (int) $promotion->usage_limit) {
                    throw ValidationException::withMessages(['promotion_code' => 'Kuota penggunaan promo sudah habis.']);
                }
                $discountCents = $discountCalculator->discountCents($promotion, $subtotalCents, $cartService);
            }

            $totalCents = max(0, $subtotalCents - $discountCents);
            $orderNumber = 'RK-'.now()->format('YmdHis').'-'.Str::upper(Str::random(5));
            $pickupCode = $data['order_type'] === Order::TYPE_TAKEAWAY ? Str::upper(Str::random(7)) : null;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'created_by' => $user->id,
                'order_source' => Order::SOURCE_WEBSITE,
                'order_type' => $data['order_type'],
                'table_number' => $data['order_type'] === Order::TYPE_DINE_IN ? trim((string) $data['table_number']) : null,
                'pickup_code' => $pickupCode,
                'customer_name_snapshot' => $customer->full_name,
                'customer_phone_snapshot' => trim((string) ($data['customer_phone'] ?? '')) ?: $customer->phone,
                'promotion_id' => $promotion?->id,
                'promotion_code_snapshot' => $promotion?->code,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $cartService->fromCents($subtotalCents),
                'discount_amount' => $cartService->fromCents($discountCents),
                'total_amount' => $cartService->fromCents($totalCents),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lineDefinitions as $line) {
                $model = $line['model'];
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['type'] === 'product' ? $model->id : null,
                    'combo_id' => $line['type'] === 'combo' ? $model->id : null,
                    'item_name_snapshot' => $model->name,
                    'item_sku_snapshot' => $model->sku,
                    'quantity' => $line['quantity'],
                    'unit_price' => $cartService->fromCents($line['unit_cents']),
                    'line_total' => $cartService->fromCents($line['line_cents']),
                ]);
            }

            foreach ($productDemand as $productId => $needed) {
                $product = $products->get($productId);
                $before = (int) $product->stock_qty;
                $after = $before - (int) $needed;
                $product->stock_qty = $after;
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'created_by' => $user->id,
                    'movement_type' => StockMovement::TYPE_SALE,
                    'quantity_change' => -(int) $needed,
                    'stock_before' => $before,
                    'stock_after' => $after,
                    'reason' => 'Reservasi stok untuk pesanan '.$order->order_number,
                    'created_at' => now(),
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'method' => $data['payment_method'],
                'status' => Payment::STATUS_PENDING,
                'amount' => $cartService->fromCents($totalCents),
                'payment_details' => $paymentDetails,
                'proof_path' => null,
                'notes' => null,
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_PENDING,
                'note' => 'Pesanan dibuat melalui website. Stok produk telah direservasi.',
                'changed_by' => $user->id,
                'created_at' => now(),
            ]);

            return $order;
        }, 3);

        $request->session()->forget(StoreCartService::SESSION_KEY);

        Notification::send(User::query()->where('role', 'admin')->where('is_active', true)->get(), new OrderCreated($order));

        return redirect()->route('customer.orders.show', $order)->with('success', 'Pesanan berhasil dibuat. Simpan nomor pesanan dan kode pickup bila takeaway.');
    }

    private function availablePublicImage(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $path = ltrim($path, '/');

        return Storage::disk('public')->exists($path) ? $path : null;
    }
}
