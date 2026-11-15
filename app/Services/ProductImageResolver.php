<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageResolver
{
    public function resolve(Product $product): ?string
    {
        $sku = strtoupper(trim((string) $product->sku));
        $skuAssetFound = false;
        $productSlug = Str::slug((string) $product->name);

        if ($sku !== '') {
            foreach (Storage::disk('public')->files('products') as $path) {
                if (preg_match('/^products\/'.preg_quote($sku, '/').'_[a-z0-9-]+\.(?:jpg|jpeg|png|webp)$/i', $path) === 1) {
                    $skuAssetFound = true;
                    $assetSlug = Str::slug(pathinfo($path, PATHINFO_FILENAME));
                    $assetSlug = Str::after($assetSlug, Str::slug($sku).'-');

                    if ($productSlug !== '' && Str::contains($assetSlug, $productSlug)) {
                        return $path;
                    }
                }
            }
        }

        if ($skuAssetFound) {
            return null;
        }

        $imagePath = ltrim((string) $product->image_path, '/');

        return $imagePath !== '' && Storage::disk('public')->exists($imagePath)
            ? $imagePath
            : null;
    }
}
