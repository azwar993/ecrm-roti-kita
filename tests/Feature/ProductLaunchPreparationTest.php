<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductLaunchPreparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_sets_demo_stock_and_activates_only_sale_ready_products_idempotently(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/ready.jpg', 'image');
        Storage::disk('public')->put('products/proposed.jpg', 'image');
        $ready = $this->product(['sku' => 'ROT-READY', 'image_path' => 'products/ready.jpg']);
        $proposed = $this->product([
            'sku' => 'ROT-074', 'name' => "S'mores Stuffed Cookie", 'image_path' => 'products/proposed.jpg',
            'price_status' => Product::PRICE_STATUS_PROPOSED,
        ]);
        $secondProposed = $this->product([
            'sku' => 'ROT-084', 'name' => 'Dubai Chocoberry Cup', 'image_path' => 'products/proposed.jpg',
            'price_status' => Product::PRICE_STATUS_PROPOSED,
        ]);
        $missingImage = $this->product(['sku' => 'ROT-NO-IMAGE', 'image_path' => null]);

        Artisan::call('products:prepare-launch');
        $preview = Artisan::output();
        $this->assertStringContainsString('STOK ROT-READY', $preview);
        $this->assertStringContainsString('TERTAHAN ROT-074', $preview);
        $this->assertStringContainsString('TERTAHAN ROT-084', $preview);
        $this->assertStringContainsString('Harga jual final belum tersedia.', $preview);
        $this->assertStringContainsString('Foto produk tidak tersedia pada public storage.', $preview);
        $this->assertSame(8, $ready->fresh()->stock_qty);

        Artisan::call('products:prepare-launch', ['--apply' => true]);
        $this->assertSame(30, $ready->fresh()->stock_qty);
        $this->assertTrue($ready->fresh()->is_active);
        $this->assertSame('ROT-READY', $ready->fresh()->sku);
        $this->assertSame('15000.00', $ready->fresh()->price);
        $this->assertSame('products/ready.jpg', $ready->fresh()->image_path);
        $this->assertFalse($proposed->fresh()->is_active);
        $this->assertFalse($secondProposed->fresh()->is_active);
        $this->assertSame(30, $proposed->fresh()->stock_qty);
        $this->assertSame(30, $secondProposed->fresh()->stock_qty);
        $this->assertFalse($missingImage->fresh()->is_active);
        $this->assertSame(30, $missingImage->fresh()->stock_qty);

        Artisan::call('products:prepare-launch', ['--apply' => true]);
        $this->assertSame(30, $ready->fresh()->stock_qty);
    }

    private function product(array $overrides = []): Product
    {
        return Product::query()->create(array_merge([
            'sku' => 'ROT-TEST-'.fake()->unique()->numerify('###'),
            'name' => 'Produk uji',
            'category' => 'Roti Manis',
            'price' => 15000,
            'stock_qty' => 8,
            'image_path' => 'products/ready.jpg',
            'is_active' => false,
            'price_status' => Product::PRICE_STATUS_FINAL,
        ], $overrides));
    }
}
