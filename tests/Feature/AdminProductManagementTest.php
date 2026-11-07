<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_open_product_management_and_admin_can_view_detail(): void
    {
        $product = $this->makeProduct(['sku' => 'ROT-900', 'name' => 'Roti Detail']);
        $filteredProduct = $this->makeProduct([
            'sku' => 'ROT-900-FILTER', 'name' => 'Pastry Draft Filter', 'category' => 'Pastry', 'is_active' => false,
        ]);
        $admin = $this->makeUser('admin');
        $customer = $this->makeUser('customer');

        $this->get(route('admin.products'))->assertRedirect(route('login'));
        $this->actingAs($customer)->get(route('admin.products'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.products'))->assertOk()->assertSee('Filter kategori');
        $this->actingAs($admin)->get(route('admin.products', ['q' => 'Draft Filter', 'category' => 'Pastry', 'status' => 'inactive']))
            ->assertOk()->assertSee($filteredProduct->name)->assertDontSee($product->name);
        $this->actingAs($admin)->get(route('admin.products.show', $product))->assertOk()->assertSee('Roti Detail');
        $this->actingAs($admin)->get(route('admin.products.edit', $product))->assertOk()->assertSee('Unggah foto baru');
    }

    public function test_admin_can_create_an_active_product_with_a_local_photo(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');
        $photo = UploadedFile::fake()->image('roti-cokelat.jpg', 800, 600);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'new-bread-01', 'name' => 'Roti Cokelat Aktual', 'category' => 'Roti Manis',
            'description' => 'Foto dan data produk aktual.', 'price' => 15000, 'stock_qty' => 9,
            'is_active' => '1', 'image' => $photo,
        ])->assertRedirect(route('admin.products'));

        $product = Product::query()->where('sku', 'NEW-BREAD-01')->firstOrFail();
        $this->assertTrue($product->is_active);
        $this->assertSame(9, $product->stock_qty);
        $this->assertStringStartsWith('products/', $product->image_path);
        Storage::disk('public')->assertExists($product->image_path);
    }

    public function test_new_products_default_to_draft_and_active_products_require_a_photo(): void
    {
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get(route('admin.products.create'))
            ->assertOk()->assertSee('Draft / nonaktif — disembunyikan');

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'NO-PHOTO-ACTIVE', 'name' => 'Belum Siap', 'category' => 'Pastry',
            'price' => 12000, 'stock_qty' => 4, 'is_active' => '1',
        ])->assertSessionHasErrors('image');

        $this->assertDatabaseMissing('products', ['sku' => 'NO-PHOTO-ACTIVE']);
    }

    public function test_product_form_rejects_duplicate_sku_invalid_price_and_invalid_stock(): void
    {
        $admin = $this->makeUser('admin');
        $this->makeProduct(['sku' => 'ROT-901']);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'rot-901', 'name' => 'SKU Ganda', 'category' => 'Pastry', 'price' => 12000,
            'stock_qty' => 1, 'is_active' => '0',
        ])->assertSessionHasErrors('sku');
        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'ROT-902', 'name' => 'Harga Tidak Valid', 'category' => 'Pastry', 'price' => 0,
            'stock_qty' => 1, 'is_active' => '0',
        ])->assertSessionHasErrors('price');
        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'ROT-903', 'name' => 'Stok Tidak Valid', 'category' => 'Pastry', 'price' => 12000,
            'stock_qty' => -1, 'is_active' => '0',
        ])->assertSessionHasErrors('stock_qty');
    }

    public function test_photo_upload_rejects_invalid_mime_and_files_over_four_megabytes(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');
        $base = ['sku' => 'ROT-910', 'name' => 'Produk Draft', 'category' => 'Pastry', 'price' => 10000, 'stock_qty' => 0, 'is_active' => '0'];

        $this->actingAs($admin)->post(route('admin.products.store'), $base + [
            'image' => UploadedFile::fake()->create('script.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('image');
        $this->actingAs($admin)->post(route('admin.products.store'), array_merge($base, [
            'sku' => 'ROT-911', 'image' => UploadedFile::fake()->image('too-large.jpg')->size(4097),
        ]))->assertSessionHasErrors('image');

        $this->assertDatabaseCount('products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('products'));
    }

    public function test_admin_can_replace_photo_without_deleting_a_photo_still_referenced_by_another_product(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/shared-old.jpg', 'old-photo');
        $product = $this->makeProduct(['sku' => 'ROT-920', 'image_path' => 'products/shared-old.jpg']);
        $this->makeProduct(['sku' => 'ROT-921', 'image_path' => 'products/shared-old.jpg']);
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'sku' => $product->sku, 'name' => $product->name, 'category' => $product->category,
            'price' => 10000, 'stock_qty' => 5, 'is_active' => '1',
            'image' => UploadedFile::fake()->image('new-photo.webp'),
        ])->assertRedirect(route('admin.products'));

        $updated = $product->fresh();
        $this->assertNotSame('products/shared-old.jpg', $updated->image_path);
        Storage::disk('public')->assertExists($updated->image_path);
        Storage::disk('public')->assertExists('products/shared-old.jpg');
    }

    public function test_home_carousel_and_public_detail_use_only_active_in_stock_database_products(): void
    {
        $available = $this->makeProduct([
            'sku' => 'LIVE-CAROUSEL-01', 'name' => 'Roti Carousel Database', 'is_active' => true,
            'stock_qty' => 3, 'price' => 18000, 'category' => 'Roti Manis',
        ]);
        $this->makeProduct(['sku' => 'INACTIVE-CAROUSEL', 'name' => 'Produk Draft Rahasia', 'is_active' => false]);
        $this->makeProduct(['sku' => 'SOLD-CAROUSEL', 'name' => 'Produk Habis', 'is_active' => true, 'stock_qty' => 0]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Pilihan Bakery')->assertSee('Roti Carousel Database')->assertSee('Rp 18.000')
            ->assertSee('data-carousel-track', false)->assertSee('data-carousel-prev', false)->assertSee('data-carousel-next', false)
            ->assertDontSee('Produk Draft Rahasia')->assertDontSee('Produk Habis');
        $this->get(route('storefront.product', $available))->assertOk()->assertSee('Roti Carousel Database');
        $this->get(route('storefront.product', Product::where('sku', 'INACTIVE-CAROUSEL')->first()))->assertNotFound();
        $this->get(route('storefront.menu', ['q' => 'Carousel', 'category' => 'Roti Manis', 'sort' => 'price_desc']))
            ->assertOk()->assertSee('LIVE-CAROUSEL-01')->assertSee('price_desc', false);
    }

    public function test_inactive_products_are_not_sellable_through_the_cart(): void
    {
        $product = $this->makeProduct(['sku' => 'DRAFT-CART-01', 'is_active' => false, 'stock_qty' => 8]);

        $this->post(route('customer.cart.items.store'), ['type' => 'product', 'id' => $product->id, 'quantity' => 1])
            ->assertSessionHasErrors('cart')->assertSessionMissing('rotikita_cart');
    }

    public function test_admin_must_have_a_stored_photo_to_activate_a_product(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');
        $product = $this->makeProduct(['sku' => 'DRAFT-ACTIVE-01', 'is_active' => false, 'image_path' => null]);

        $this->actingAs($admin)->patch(route('admin.products.active', $product))
            ->assertSessionHasErrors('image');
        $this->assertFalse($product->fresh()->is_active);

        Storage::disk('public')->put('products/approved-team-photo.jpg', 'valid-local-file');
        $product->update(['image_path' => 'products/approved-team-photo.jpg']);
        $this->actingAs($admin)->patch(route('admin.products.active', $product))->assertSessionHasNoErrors();
        $this->assertTrue($product->fresh()->is_active);

        $this->actingAs($admin)->delete(route('admin.products.destroy', $product));
        $this->assertFalse($product->fresh()->is_active);
        Storage::disk('public')->assertExists('products/approved-team-photo.jpg');
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create([
            'name' => ucfirst($role).' Test', 'email' => $role.'-'.uniqid().'@rotikita.test',
            'password' => 'password', 'role' => $role, 'is_active' => true,
        ]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        static $sequence = 0;
        $sequence++;

        return Product::create(array_merge([
            'sku' => 'TEST-PRODUCT-'.$sequence, 'name' => 'Produk Uji '.$sequence,
            'category' => 'Roti Manis', 'price' => 10000, 'stock_qty' => 5,
            'is_active' => true, 'image_path' => null,
        ], $overrides));
    }
}
