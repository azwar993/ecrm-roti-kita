<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductLaunchPreparationService
{
    /** @return array{total:int,ready:array<int,Product>,blocked:array<int,array{product:Product,reasons:array<int,string>}>,stock_changes:array<int,Product>} */
    public function preview(): array
    {
        $products = Product::query()->orderBy('id')->get();
        $ready = [];
        $blocked = [];

        foreach ($products as $product) {
            $reasons = [];
            if (blank($product->name)) {
                $reasons[] = 'Nama produk kosong.';
            }
            if (! is_string($product->sku) || preg_match('/^[A-Z0-9][A-Z0-9._-]*$/i', $product->sku) !== 1) {
                $reasons[] = 'SKU kosong atau tidak valid.';
            }
            if ((float) $product->price <= 0 || ($product->price_status ?? Product::PRICE_STATUS_FINAL) !== Product::PRICE_STATUS_FINAL) {
                $reasons[] = 'Harga jual final belum tersedia.';
            }
            if (blank($product->category) || ! in_array($product->category, [...Product::CATEGORIES, ...Product::LEGACY_CATEGORIES], true)) {
                $reasons[] = 'Kategori kosong atau tidak valid.';
            }
            if (blank($product->displayImagePath())) {
                $reasons[] = 'Foto produk tidak tersedia pada public storage.';
            }

            if ($reasons === []) {
                $ready[] = $product;
            } else {
                $blocked[] = ['product' => $product, 'reasons' => $reasons];
            }
        }

        return [
            'total' => $products->count(),
            'ready' => $ready,
            'blocked' => $blocked,
            'stock_changes' => $products->filter(fn (Product $product): bool => $product->stock_qty !== 30)->values()->all(),
        ];
    }

    public function apply(): array
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:'
            || filled(config('database.connections.sqlite.url'))) {
            throw new RuntimeException('Product launch preparation is restricted to SQLite in-memory.');
        }

        $report = $this->preview();

        DB::transaction(function () use ($report): void {
            Product::query()->where('stock_qty', '!=', 30)->update(['stock_qty' => 30]);

            foreach ($report['ready'] as $product) {
                if (! $product->is_active) {
                    $product->update(['is_active' => true]);
                }
            }
        });

        return $report;
    }
}
