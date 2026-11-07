<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Services\StoreCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request, StoreCartService $cartService): View
    {
        $cart = $cartService->build($request);

        return view('customer.cart', $cart);
    }

    public function store(Request $request, StoreCartService $cartService): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:product,combo'],
            'id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $type = $data['type'];
        $id = (int) $data['id'];
        $model = $cartService->findSellable($type, $id);
        if (! $model) {
            throw ValidationException::withMessages(['cart' => 'Produk atau paket sudah tidak tersedia.']);
        }

        $key = $type.'_'.$id;
        $cart = $request->session()->get(StoreCartService::SESSION_KEY, []);
        if (! is_array($cart)) {
            $cart = [];
        }
        $newQuantity = (int) ($cart[$key] ?? 0) + (int) $data['quantity'];
        $available = $cartService->availableQuantity($type, $model);

        if ($newQuantity > $available) {
            throw ValidationException::withMessages(['cart' => "Stok tersedia untuk item ini hanya {$available} paket/unit."]);
        }
        if ($newQuantity > 99) {
            throw ValidationException::withMessages(['cart' => 'Maksimal 99 unit untuk satu jenis item.']);
        }

        $cart[$key] = $newQuantity;
        $request->session()->put(StoreCartService::SESSION_KEY, $cart);

        $count = array_sum(array_map('intval', $cart));
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Produk berhasil ditambahkan ke keranjang.', 'cart_count' => $count]);
        }

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, string $type, int $id, StoreCartService $cartService): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);
        abort_unless(in_array($type, ['product', 'combo'], true), 404);

        $model = $cartService->findSellable($type, $id);
        if (! $model) {
            throw ValidationException::withMessages(['cart' => 'Item tidak lagi tersedia. Hapus item tersebut dari keranjang.']);
        }

        $available = $cartService->availableQuantity($type, $model);
        if ((int) $data['quantity'] > $available) {
            throw ValidationException::withMessages(['quantity' => "Stok yang tersedia saat ini {$available} unit/paket."]);
        }

        $cart = $request->session()->get(StoreCartService::SESSION_KEY, []);
        $cart[$type.'_'.$id] = (int) $data['quantity'];
        $request->session()->put(StoreCartService::SESSION_KEY, $cart);

        return redirect()->route('customer.cart')->with('success', 'Jumlah item diperbarui.');
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['product', 'combo'], true), 404);
        $cart = $request->session()->get(StoreCartService::SESSION_KEY, []);
        unset($cart[$type.'_'.$id]);
        $request->session()->put(StoreCartService::SESSION_KEY, $cart);

        return redirect()->route('customer.cart')->with('success', 'Item dihapus dari keranjang.');
    }
}
