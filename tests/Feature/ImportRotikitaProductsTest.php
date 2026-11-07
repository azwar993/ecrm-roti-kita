<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ImportRotikitaProductsTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $sourceDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->sourceDirectories as $directory) {
            if (is_dir($directory)) {
                File::deleteDirectory($directory);
            }
        }

        parent::tearDown();
    }

    public function test_import_skips_existing_skus_and_persists_units_proposed_prices_and_public_photos(): void
    {
        Storage::fake('public');
        $existing = Product::create([
            'sku' => 'ROT-045', 'name' => 'Produk Lama Tetap', 'category' => 'Roti Manis',
            'description' => 'Data asli', 'price' => 15000, 'stock_qty' => 7,
            'image_path' => 'products/original.jpg', 'is_active' => true,
        ]);
        $sourceDirectory = $this->makeImportPackage([
            $this->row('ROT-045', 'Produk Berkonflik', false),
            $this->row('ROT-046', 'Roti Final', false, 'loaf', 25000),
            $this->row('ROT-074', "S'mores Stuffed Cookie", true, 'pcs', 28000),
        ]);

        $this->artisan('rotikita:import-products', [
            '--source' => $sourceDirectory,
            '--expected-count' => 3,
        ])->assertExitCode(0)
            ->expectsOutputToContain('Berhasil: 2')
            ->expectsOutputToContain('Dilewati (SKU sudah ada): 1')
            ->expectsOutputToContain('Foto baru disalin: 2');

        $existing->refresh();
        $this->assertSame('Produk Lama Tetap', $existing->name);
        $this->assertSame('15000.00', $existing->price);
        $this->assertSame(7, $existing->stock_qty);
        $this->assertTrue($existing->is_active);

        $finalProduct = Product::query()->where('sku', 'ROT-046')->firstOrFail();
        $this->assertSame('Roti Final', $finalProduct->name);
        $this->assertSame('loaf', $finalProduct->unit);
        $this->assertSame(Product::PRICE_STATUS_FINAL, $finalProduct->price_status);
        $this->assertSame(0, $finalProduct->stock_qty);
        $this->assertFalse($finalProduct->is_active);
        $this->assertSame('products/ROT-046_roti-final.jpg', $finalProduct->image_path);

        $proposedProduct = Product::query()->where('sku', 'ROT-074')->firstOrFail();
        $this->assertSame(28000, (int) $proposedProduct->price);
        $this->assertSame('pcs', $proposedProduct->unit);
        $this->assertSame(Product::PRICE_STATUS_PROPOSED, $proposedProduct->price_status);
        $this->assertStringContainsString('konfirmasi pemilik', mb_strtolower($proposedProduct->admin_note));
        $this->assertFalse($proposedProduct->is_active);
        Storage::disk('public')->assertExists($finalProduct->image_path);
        Storage::disk('public')->assertExists($proposedProduct->image_path);
        $this->assertStringEndsWith($finalProduct->image_path, Storage::disk('public')->url($finalProduct->image_path));

        $this->artisan('rotikita:import-products', [
            '--source' => $sourceDirectory,
            '--expected-count' => 3,
        ])->assertExitCode(0)
            ->expectsOutputToContain('Berhasil: 0')
            ->expectsOutputToContain('Dilewati (SKU sudah ada): 3')
            ->expectsOutputToContain('Foto baru disalin: 0');

        $this->assertSame(3, Product::query()->count());
        Storage::disk('public')->assertExists($finalProduct->image_path);
    }

    public function test_missing_source_photo_aborts_before_database_or_storage_changes(): void
    {
        Storage::fake('public');
        $sourceDirectory = $this->makeImportPackage([
            $this->row('ROT-045', 'Roti Pertama', false),
            $this->row('ROT-046', 'Roti Kedua', false),
        ], ['ROT-046']);

        $this->artisan('rotikita:import-products', [
            '--source' => $sourceDirectory,
            '--expected-count' => 2,
        ])->assertExitCode(1)
            ->expectsOutputToContain('Import dibatalkan sebelum perubahan');

        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_different_existing_photo_is_reported_as_conflict_without_overwrite(): void
    {
        Storage::fake('public');
        $sourceDirectory = $this->makeImportPackage([
            $this->row('ROT-045', 'Foto Konflik', false),
            $this->row('ROT-046', 'Foto Aman', false),
        ]);
        $conflictingPath = 'products/ROT-045_foto-konflik.jpg';
        Storage::disk('public')->put($conflictingPath, 'existing file owned by another record');

        $this->artisan('rotikita:import-products', [
            '--source' => $sourceDirectory,
            '--expected-count' => 2,
        ])->assertExitCode(0)
            ->expectsOutputToContain('Berhasil: 1')
            ->expectsOutputToContain('Konflik file: 1');

        $this->assertDatabaseMissing('products', ['sku' => 'ROT-045']);
        $this->assertDatabaseHas('products', ['sku' => 'ROT-046', 'is_active' => 0, 'stock_qty' => 0]);
        $this->assertSame('existing file owned by another record', Storage::disk('public')->get($conflictingPath));
    }

    public function test_product_with_proposed_price_cannot_be_activated(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/proposed.jpg', 'photo');
        $admin = User::create([
            'name' => 'Admin Import Test', 'email' => 'admin-import-test@example.test', 'password' => 'password',
        ]);
        $admin->role = 'admin';
        $admin->is_active = true;
        $admin->save();
        $product = Product::create([
            'sku' => 'ROT-074', 'name' => "S'mores Stuffed Cookie", 'category' => 'Cookies',
            'price' => 28000, 'unit' => 'pcs', 'price_status' => Product::PRICE_STATUS_PROPOSED,
            'admin_note' => 'Harga usulan; tunggu konfirmasi pemilik.', 'stock_qty' => 0,
            'is_active' => false, 'image_path' => 'products/proposed.jpg',
        ]);

        $this->actingAs($admin)->patch(route('admin.products.active', $product))
            ->assertSessionHasErrors('price');

        $this->assertFalse($product->fresh()->is_active);
    }

    /** @param list<array<string, mixed>> $rows @param list<string> $missingPhotos */
    private function makeImportPackage(array $rows, array $missingPhotos = []): string
    {
        $directory = storage_path('framework/testing/rotikita-import-'.Str::random(12));
        $photoDirectory = $directory.DIRECTORY_SEPARATOR.'photos';
        File::ensureDirectoryExists($photoDirectory);
        $this->sourceDirectories[] = $directory;

        $handle = fopen($directory.DIRECTORY_SEPARATOR.'produk_import_40.csv', 'wb');
        fputcsv($handle, [
            'sku', 'nama_produk', 'kategori', 'harga_draft_rp', 'satuan', 'deskripsi_draft',
            'nama_file_foto', 'stok_awal', 'status_awal', 'perlu_persetujuan_harga', 'catatan',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['sku'], $row['name'], 'Roti Manis', $row['price'], $row['unit'],
                'Deskripsi draft.', $row['photo'], 0, 'draft / nonaktif', $row['proposed'] ? 'YA' : 'TIDAK',
                $row['proposed'] ? 'Harga usulan—perlu konfirmasi pemilik.' : 'Deskripsi draft; perlu review.',
            ]);

            if (! in_array($row['sku'], $missingPhotos, true)) {
                $uploadedImage = UploadedFile::fake()->image('source.jpg', 24, 24);
                file_put_contents($photoDirectory.DIRECTORY_SEPARATOR.$row['photo'], file_get_contents($uploadedImage->getRealPath()));
            }
        }

        fclose($handle);

        return $directory;
    }

    /** @return array{sku: string, name: string, price: int, unit: string, photo: string, proposed: bool} */
    private function row(string $sku, string $name, bool $proposed, string $unit = 'pcs', int $price = 18000): array
    {
        $slug = Str::slug($name);

        return [
            'sku' => $sku,
            'name' => $name,
            'price' => $price,
            'unit' => $unit,
            'photo' => $sku.'_'.$slug.'.jpg',
            'proposed' => $proposed,
        ];
    }
}
