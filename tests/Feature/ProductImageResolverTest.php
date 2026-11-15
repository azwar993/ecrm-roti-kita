<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_sku_named_asset_takes_priority_over_a_legacy_shared_image_path(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/ROT-045_ube-coconut-milk-bun.jpg', 'sku-photo');
        Storage::disk('public')->put('products/cookies.jpg', 'legacy-photo');
        $product = $this->product([
            'sku' => 'ROT-045',
            'name' => 'Ube Coconut Milk Bun',
            'image_path' => 'products/cookies.jpg',
        ]);

        $resolvedPath = $product->displayImagePath();

        $this->assertSame('products/ROT-045_ube-coconut-milk-bun.jpg', $resolvedPath);
    }

    public function test_missing_or_invalid_image_uses_no_photo_fallback(): void
    {
        Storage::fake('public');
        $product = $this->product([
            'sku' => 'ROT-004',
            'image_path' => 'products/not-found.jpg',
        ]);

        $this->assertNull($product->displayImagePath());
    }

    public function test_sku_asset_with_a_different_product_name_is_not_used(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/ROT-045_ube-coconut-milk-bun.jpg', 'sku-photo');
        Storage::disk('public')->put('products/cookies.jpg', 'legacy-photo');
        $product = $this->product([
            'sku' => 'ROT-045',
            'name' => 'Butter Cookies',
            'category' => 'Cookies',
            'image_path' => 'products/cookies.jpg',
        ]);

        $this->assertNull($product->displayImagePath());
    }

    public function test_valid_non_sku_image_is_preserved_for_manually_uploaded_products(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/manual-pastry.webp', 'pastry-photo');
        $product = $this->product([
            'sku' => 'MANUAL-001',
            'category' => 'Pastry',
            'image_path' => 'products/manual-pastry.webp',
        ]);

        $this->assertSame('products/manual-pastry.webp', $product->displayImagePath());
    }

    public function test_product_card_and_detail_render_a_clear_category_fallback(): void
    {
        Storage::fake('public');
        $product = $this->product([
            'sku' => 'ROT-004',
            'name' => 'Roti Cokelat Keju',
            'category' => 'Roti Manis',
            'image_path' => null,
            'is_active' => true,
        ]);

        $this->get(route('storefront.menu', ['q' => $product->sku]))
            ->assertOk()
            ->assertSee('Foto Roti Manis belum tersedia');
        $this->get(route('storefront.product', $product))
            ->assertOk()
            ->assertSee('Foto Roti Manis belum tersedia');
    }

    public function test_loaded_image_fallback_contract_hides_fallback_and_handles_load_and_error_events(): void
    {
        $script = file_get_contents(public_path('js/rotikita-storefront.js'));
        $storefrontStyles = file_get_contents(public_path('css/rotikita-storefront.css'));
        $applicationStyles = file_get_contents(public_path('css/rotikita.css'));

        $this->assertIsString($script);
        $this->assertStringContainsString("document.addEventListener('load'", $script);
        $this->assertStringContainsString("document.addEventListener('error'", $script);
        $this->assertStringContainsString('else hideImageFallback(image);', $script);
        $this->assertIsString($storefrontStyles);
        $this->assertStringContainsString('[hidden] { display: none !important; }', $storefrontStyles);
        $this->assertIsString($applicationStyles);
        $this->assertStringContainsString('[hidden] { display: none !important; }', $applicationStyles);
    }

    private function product(array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'sku' => 'ROT-TEST-'.fake()->unique()->numerify('###'),
            'name' => 'Produk uji',
            'category' => 'Roti Manis',
            'price' => 15000,
            'stock_qty' => 8,
            'image_path' => null,
            'is_active' => false,
            'price_status' => Product::PRICE_STATUS_FINAL,
        ], $overrides));
    }
}
