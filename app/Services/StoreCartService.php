<?php

namespace App\Services;

use App\Models\Combo;
use App\Models\Product;
use Illuminate\Http\Request;

class StoreCartService
{
    public const SESSION_KEY = 'rotikita_cart';

    /** Build cart display data from current database prices (never trust prices in session). */
    public function build(Request $request): array
    {
        $sessionCart = $request->session()->get(self::SESSION_KEY, []);
        if (! is_array($sessionCart)) {
            $sessionCart = [];
        }

        $cleanCart = [];
        $items = [];
        $subtotalCents = 0;
        $cartCount = 0;

        foreach ($sessionCart as $key => $rawQuantity) {
            if (! preg_match('/^(product|combo)_(\d+)$/', (string) $key, $matches)) {
                continue;
            }

            $type = $matches[1];
            $id = (int) $matches[2];
            $quantity = filter_var($rawQuantity, FILTER_VALIDATE_INT);
            if ($quantity === false || $quantity < 1 || $quantity > 99) {
                continue;
            }

            $model = $this->findSellable($type, $id);
            if (! $model) {
                continue;
            }

            $available = $this->availableQuantity($type, $model);
            $unitPriceCents = $this->toCents($model->price);
            $lineTotalCents = $unitPriceCents * $quantity;

            $cleanCart[$key] = $quantity;
            $items[] = [
                'key' => $key,
                'type' => $type,
                'id' => $id,
                'name' => $model->name,
                'sku' => $model->sku,
                'description' => $model->description,
                'image_path' => $model->image_path,
                'quantity' => $quantity,
                'unit_price_cents' => $unitPriceCents,
                'line_total_cents' => $lineTotalCents,
                'available_quantity' => $available,
            ];

            $subtotalCents += $lineTotalCents;
            $cartCount += $quantity;
        }

        $request->session()->put(self::SESSION_KEY, $cleanCart);

        return [
            'items' => $items,
            'subtotal_cents' => $subtotalCents,
            'cart_count' => $cartCount,
        ];
    }

    public function findSellable(string $type, int $id): Product|Combo|null
    {
        if ($type === 'product') {
            return Product::query()->where('is_active', true)->find($id);
        }

        if ($type !== 'combo') {
            return null;
        }

        $now = now();
        return Combo::query()
            ->with('items.product')
            ->where('is_active', true)
            ->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->find($id);
    }

    public function availableQuantity(string $type, Product|Combo $model): int
    {
        if ($type === 'product') {
            return max(0, (int) $model->stock_qty);
        }

        $combo = $model instanceof Combo ? $model : null;
        if (! $combo || $combo->items->isEmpty()) {
            return 0;
        }

        $available = PHP_INT_MAX;
        foreach ($combo->items as $comboItem) {
            $product = $comboItem->product;
            $perCombo = max(1, (int) $comboItem->quantity);
            if (! $product || ! $product->is_active) {
                return 0;
            }
            $available = min($available, intdiv((int) $product->stock_qty, $perCombo));
        }

        return $available === PHP_INT_MAX ? 0 : max(0, $available);
    }

    public function toCents(int|float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public function fromCents(int $cents): string
    {
        $cents = max(0, $cents);
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
