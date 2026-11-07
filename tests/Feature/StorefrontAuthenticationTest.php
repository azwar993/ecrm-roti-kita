<?php

namespace Tests\Feature;

use App\Models\Combo;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\PaymentStatusChanged;
use App\Services\RevenueService;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_open_the_public_home_and_menu(): void
    {
        $this->get(route('home'))->assertOk()->assertViewIs('storefront.home')
            ->assertSee('<title>RotiKita Bakery — Roti Hangat Setiap Hari</title>', false)
            ->assertSee('favicon.svg?v=', false)
            ->assertSee('favicon.ico?v=', false)
            ->assertSee('apple-touch-icon.png?v=', false)
            ->assertDontSee('Laravel Logo')
            ->assertSee('Masuk')
            ->assertSee('Daftar akun')
            ->assertSee('Belum ada produk tersedia')
            ->assertDontSee('data-account-trigger', false);
        $this->get(route('storefront.menu'))->assertOk()->assertViewIs('storefront.menu');
        $this->get('/route-not-found-for-brand-check')->assertNotFound()
            ->assertSee('<title>Halaman tidak ditemukan — RotiKita Bakery</title>', false)
            ->assertSee('favicon.svg?v=', false);
    }

    public function test_auth_and_dashboard_pages_share_rotikita_branding_and_favicon(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('<title>Masuk — RotiKita Bakery</title>', false)
            ->assertSee('favicon.svg?v=', false);

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()
            ->assertSee('<title>Dashboard Admin — RotiKita Bakery</title>', false)
            ->assertSee('favicon.svg?v=', false)
            ->assertDontSee('Laravel', false);
    }

    public function test_storefront_catalogs_show_active_database_products_and_hide_inactive_products(): void
    {
        $active = Product::create([
            'sku' => 'LIVE-BREAD-01', 'name' => 'Roti Database Aktif', 'category' => 'Roti manis',
            'description' => 'Data aktif dari database.', 'price' => 18000, 'stock_qty' => 7,
            'is_active' => true, 'image_path' => 'products/not-found.jpg',
        ]);
        Product::create([
            'sku' => 'HIDDEN-BREAD-02', 'name' => 'Roti Nonaktif Rahasia', 'category' => 'Pastry',
            'price' => 22000, 'stock_qty' => 12, 'is_active' => false,
        ]);
        $cake = Product::create([
            'sku' => 'LIVE-CAKE-03', 'name' => 'Cake Database Aktif', 'category' => 'Cake & Tart',
            'price' => 45000, 'stock_qty' => 3, 'is_active' => true,
        ]);
        $customer = $this->makeUser('customer');

        $this->get(route('home'))->assertOk()
            ->assertSee($active->name)->assertSee('Rp 18.000')
            ->assertSee($cake->name)
            ->assertSee('Cake & Tart')
            ->assertSee('data-store-image-fallback', false)
            ->assertDontSee('Belum ada produk aktif')
            ->assertDontSee('Roti Nonaktif Rahasia');
        $this->get(route('storefront.menu'))->assertOk()
            ->assertSee($active->sku)->assertSee('Tersedia')->assertSee('Cake & Tart')->assertSee('Donat')
            ->assertDontSee('Roti Nonaktif Rahasia');
        $this->get(route('storefront.menu', ['category' => 'Cake & Tart']))->assertOk()
            ->assertSee($cake->sku)->assertDontSee($active->sku)->assertDontSee('Roti Nonaktif Rahasia');
        $this->get(route('storefront.menu', ['q' => 'Tart']))->assertOk()
            ->assertSee($cake->sku)->assertDontSee('Roti Nonaktif Rahasia');
        $this->get(route('storefront.menu', ['category' => 'Cookies']))->assertOk()
            ->assertSee('Belum ada produk aktif di kategori Cookies');
        $this->actingAs($customer)->get(route('customer.catalog'))->assertOk()
            ->assertSee($active->name)->assertSee($cake->name)->assertSee('Cake & Tart')->assertSee('Snack Bakery')
            ->assertSee('data-store-image-fallback', false)
            ->assertDontSee('Roti Nonaktif Rahasia')->assertDontSee('>Minuman<', false);
        $this->actingAs($customer)->get(route('customer.catalog', ['category' => 'Cookies']))->assertOk()
            ->assertSee('Belum ada produk aktif di kategori Cookies');
        $this->actingAs($customer)->get(route('customer.catalog', ['q' => 'Tart']))->assertOk()
            ->assertSee($cake->sku)->assertDontSee('Roti Nonaktif Rahasia');
    }

    public function test_admin_product_form_uses_bakery_categories_and_preserves_legacy_values(): void
    {
        $admin = $this->makeUser('admin');
        $legacy = Product::create([
            'sku' => 'LEGACY-ROTI-01', 'name' => 'Produk Kategori Lama', 'category' => 'Kecut',
            'price' => 10000, 'stock_qty' => 4, 'is_active' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.products.create'))->assertOk()
            ->assertSee('value="Roti Manis"', false)->assertSee('value="Roti Tawar"', false)
            ->assertSee('value="Cake &amp; Dessert"', false)->assertSee('value="Cake &amp; Tart"', false)
            ->assertSee('value="Snack Bakery"', false)->assertDontSee('value="Minuman"', false)
            ->assertSee('value="Kecut"', false);
        $this->actingAs($admin)->get(route('admin.products.edit', $legacy))->assertOk()
            ->assertSee('value="Kecut" selected', false);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'NEW-CAKE-01', 'name' => 'Cake Uji', 'category' => 'Cake & Tart',
            'price' => 45000, 'stock_qty' => 5, 'is_active' => '0',
        ])->assertRedirect(route('admin.products'));
        $this->assertDatabaseHas('products', ['sku' => 'NEW-CAKE-01', 'category' => 'Cake & Tart']);

        $this->actingAs($admin)->post(route('admin.products.store'), [
            'sku' => 'INVALID-CATEGORY', 'name' => 'Kategori Tidak Dikenal', 'category' => 'Kategori Tebakan',
            'price' => 1000, 'stock_qty' => 1, 'is_active' => '1',
        ])->assertSessionHasErrors('category');
        $this->assertDatabaseMissing('products', ['sku' => 'INVALID-CATEGORY']);
        $this->assertSame('Kecut', $legacy->fresh()->category);
    }

    public function test_login_and_register_views_render_one_toggle_icon_per_password_field(): void
    {
        $login = $this->get(route('login'))->assertOk()->assertDontSee('data-eye-closed', false)->getContent();
        $register = $this->get(route('register'))->assertOk()->assertDontSee('data-eye-closed', false)->getContent();

        foreach ([$login, $register] as $html) {
            preg_match_all('/<button[^>]*data-password-toggle[^>]*>(.*?)<\/button>/s', $html, $buttons);
            $this->assertNotEmpty($buttons[1]);
            foreach ($buttons[1] as $buttonMarkup) {
                $this->assertSame(1, substr_count($buttonMarkup, '<svg'));
            }
        }
        $this->assertStringContainsString('aria-pressed="false"', $login);
        $this->assertStringContainsString('data-target="password_confirmation"', $register);
    }

    public function test_customer_login_returns_to_public_home_even_with_stale_dashboard_intended_url(): void
    {
        $user = $this->makeUser('customer');

        $this->withSession(['url.intended' => route('customer.dashboard')])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));

        $this->get(route('home'))->assertOk()->assertViewIs('storefront.home')
            ->assertSee('data-account-trigger', false)
            ->assertSee('Dashboard saya')
            ->assertSee('Pesanan saya')
            ->assertDontSee('>Ke dashboard<', false)
            ->assertDontSee('Daftar akun');
        $this->get(route('customer.dashboard'))->assertOk();
    }

    public function test_admin_login_returns_to_public_home(): void
    {
        $user = $this->makeUser('admin');

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('home'));
        $this->get(route('home'))->assertOk()->assertSee('Dashboard admin')->assertSee('Kelola combo')
            ->assertSee('Kelola pesanan')->assertDontSee('>Ke dashboard<', false);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_seeder_creates_once_and_never_resets_an_existing_account(): void
    {
        $keys = ['ROTIKITA_ADMIN_PASSWORD', 'ROTIKITA_ADMIN_EMAIL'];
        $previous = [];
        foreach ($keys as $key) {
            $previous[$key] = [getenv($key), array_key_exists($key, $_ENV), $_ENV[$key] ?? null, array_key_exists($key, $_SERVER), $_SERVER[$key] ?? null];
        }

        try {
            putenv('ROTIKITA_ADMIN_EMAIL=admin@rotikita.test');
            $_ENV['ROTIKITA_ADMIN_EMAIL'] = $_SERVER['ROTIKITA_ADMIN_EMAIL'] = 'admin@rotikita.test';
            $this->withAdminSeederPassword('First-Temporary-Admin-Password-2026');
            (new AdminSeeder)->run();

            $admin = User::query()->where('email', 'admin@rotikita.test')->firstOrFail();
            $originalHash = $admin->password;
            $this->assertTrue(Hash::check('First-Temporary-Admin-Password-2026', $originalHash));
            $this->assertSame(1, User::query()->where('email', 'admin@rotikita.test')->count());

            $admin->name = 'Nama Admin Disesuaikan';
            $admin->is_active = false;
            $admin->save();
            $this->withAdminSeederPassword('Second-Temporary-Admin-Password-2026');
            (new AdminSeeder)->run();

            $admin->refresh();
            $this->assertSame($originalHash, $admin->password);
            $this->assertSame('Nama Admin Disesuaikan', $admin->name);
            $this->assertFalse($admin->is_active);
            $this->assertSame(1, User::query()->where('email', 'admin@rotikita.test')->count());
        } finally {
            foreach ($previous as $key => [$processValue, $hadEnv, $envValue, $hadServer, $serverValue]) {
                if ($processValue === false) {
                    putenv($key);
                } else {
                    putenv($key.'='.$processValue);
                }

                if ($hadEnv) {
                    $_ENV[$key] = $envValue;
                } else {
                    unset($_ENV[$key]);
                }

                if ($hadServer) {
                    $_SERVER[$key] = $serverValue;
                } else {
                    unset($_SERVER[$key]);
                }
            }
        }
    }

    public function test_logout_returns_to_storefront_and_restores_guest_navigation(): void
    {
        $user = $this->makeUser('customer');

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('home'));
        $this->get(route('home'))->assertOk()
            ->assertSee('Masuk')
            ->assertSee('Daftar akun')
            ->assertDontSee('data-account-trigger', false);
    }

    public function test_registration_from_checkout_returns_to_checkout_and_consumes_marker(): void
    {
        $this->withSession([
            'url.intended' => route('customer.checkout'),
            'rotikita.auth_context' => 'checkout',
            'rotikita_cart' => ['product_77' => 2],
        ])->post(route('register.store'), [
            'name' => 'Customer Baru',
            'email' => 'baru@example.test',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => '1',
        ])->assertRedirect(route('customer.checkout'))
            ->assertSessionHas('rotikita_cart', ['product_77' => 2])
            ->assertSessionMissing('rotikita.auth_context');
    }

    public function test_normal_registration_ignores_a_stale_dashboard_intended_url(): void
    {
        $this->withSession(['url.intended' => route('customer.dashboard')])
            ->post(route('register.store'), [
                'name' => 'Customer Baru',
                'email' => 'baru-home@example.test',
                'password' => 'password',
                'password_confirmation' => 'password',
                'terms' => '1',
            ])->assertRedirect(route('home'));
    }

    public function test_checkout_intended_destination_and_session_cart_survive_login(): void
    {
        $user = $this->makeUser('customer');
        $cart = ['product_123' => 2];

        $this->withSession(['rotikita_cart' => $cart])
            ->get(route('customer.checkout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('rotikita.auth_context', 'checkout')
            ->assertSessionHas('url.intended', route('customer.checkout'));

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('customer.checkout'))
            ->assertSessionHas('rotikita_cart', $cart);
    }

    public function test_dashboard_is_only_reached_by_its_explicit_route_and_has_real_empty_metrics(): void
    {
        $user = $this->makeUser('customer');

        $this->actingAs($user)->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Total pesanan')
            ->assertSee('Belum ada pesanan')
            ->assertDontSee('MODE PREVIEW')
            ->assertDontSee('RK-1047');
    }

    public function test_customer_dashboard_metrics_and_recent_orders_are_scoped_to_its_database_records(): void
    {
        $user = $this->makeUser('customer');
        $customer = $this->makeCustomer($user, 'CUS-DASH-01');
        $otherUser = User::create(['name' => 'Pelanggan Lain', 'email' => 'other-dash@example.test', 'password' => 'password']);
        $otherUser->role = 'customer';
        $otherUser->is_active = true;
        $otherUser->save();
        $otherCustomer = $this->makeCustomer($otherUser, 'CUS-DASH-02');

        $pending = Order::create([
            'order_number' => 'RK-DASH-PENDING', 'customer_id' => $customer->id, 'created_by' => $user->id,
            'order_source' => Order::SOURCE_WEBSITE, 'order_type' => Order::TYPE_TAKEAWAY,
            'status' => Order::STATUS_PREPARING, 'subtotal' => 5000, 'discount_amount' => 0, 'total_amount' => 5000,
        ]);
        $completed = Order::create([
            'order_number' => 'RK-DASH-COMPLETED', 'customer_id' => $customer->id, 'created_by' => $user->id,
            'order_source' => Order::SOURCE_WEBSITE, 'order_type' => Order::TYPE_TAKEAWAY,
            'status' => Order::STATUS_COMPLETED, 'subtotal' => 12000, 'discount_amount' => 0, 'total_amount' => 12000,
        ]);
        Order::create([
            'order_number' => 'RK-FOREIGN-PRIVATE', 'customer_id' => $otherCustomer->id, 'created_by' => $otherUser->id,
            'order_source' => Order::SOURCE_WEBSITE, 'order_type' => Order::TYPE_TAKEAWAY,
            'status' => Order::STATUS_PENDING, 'subtotal' => 99000, 'discount_amount' => 0, 'total_amount' => 99000,
        ]);
        Payment::create(['order_id' => $pending->id, 'method' => Payment::METHOD_CASH, 'status' => Payment::STATUS_PENDING, 'amount' => 5000]);
        Payment::create(['order_id' => $completed->id, 'method' => Payment::METHOD_QRIS, 'status' => Payment::STATUS_PAID, 'amount' => 12000]);
        Feedback::create(['customer_id' => $customer->id, 'type' => Feedback::TYPE_REVIEW, 'rating' => 5, 'message' => 'Roti sangat enak dan layanan ramah.', 'status' => Feedback::STATUS_NEW]);

        $this->actingAs($user)->get(route('customer.dashboard'))->assertOk()
            ->assertSee('RK-DASH-PENDING')->assertSee('RK-DASH-COMPLETED')
            ->assertSee('Pending')->assertSee('Paid')
            ->assertSee('<div class="rk-stat-value">2</div>', false)
            ->assertSee('<div class="rk-stat-value">1</div>', false)
            ->assertDontSee('RK-FOREIGN-PRIVATE')
            ->assertDontSee('Belum ada pesanan');
    }

    public function test_admin_dashboard_uses_the_live_dashboard_view_without_preview_copy(): void
    {
        $user = $this->makeUser('admin');

        $this->actingAs($user)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total pelanggan')
            ->assertDontSee('MODE PREVIEW')
            ->assertDontSee('RK-1048');
    }

    public function test_customer_and_admin_routes_reject_the_other_role(): void
    {
        $customer = $this->makeUser('customer');
        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get(route('customer.dashboard'))->assertForbidden();
    }

    public function test_admin_customer_management_uses_real_paginated_profiles_and_search(): void
    {
        $admin = $this->makeUser('admin');
        $customerUser = $this->makeUser('customer');
        $customer = $this->makeCustomer($customerUser, 'CUS-CRM-001');
        $customer->update([
            'phone' => '081234567890',
            'email_marketing_consent' => true,
            'consent_updated_at' => now(),
        ]);
        $order = Order::create([
            'order_number' => 'RK-CRM-001', 'customer_id' => $customer->id, 'created_by' => $customerUser->id,
            'order_source' => Order::SOURCE_WEBSITE, 'order_type' => Order::TYPE_TAKEAWAY,
            'status' => Order::STATUS_COMPLETED, 'subtotal' => 25000, 'discount_amount' => 0, 'total_amount' => 25000,
        ]);
        Feedback::create([
            'customer_id' => $customer->id, 'order_id' => $order->id,
            'type' => Feedback::TYPE_REVIEW, 'rating' => 5, 'message' => 'CRM feedback aktual', 'status' => Feedback::STATUS_NEW,
        ]);

        $this->get(route('admin.customers'))->assertRedirect(route('login'));
        $this->actingAs($customerUser)->get(route('admin.customers'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.customers', ['q' => 'CUS-CRM-001', 'filter' => 'with_orders']))
            ->assertOk()->assertViewIs('admin.customers')
            ->assertSee('Customer Test')->assertSee('CUS-CRM-001')->assertSee('Diizinkan')
            ->assertSee(route('admin.customers.show', $customer));
        $this->actingAs($admin)->get(route('admin.customers', ['q' => 'not-found']))
            ->assertOk()->assertSee('Tidak ada pelanggan yang cocok');
        $this->actingAs($admin)->get(route('admin.customers.show', $customer))
            ->assertOk()->assertViewIs('admin.customer-show')
            ->assertSee('RK-CRM-001')->assertSee('CRM feedback aktual')->assertSee('Rp 25.000');
    }

    public function test_storefront_product_and_combo_images_have_load_failure_fallbacks(): void
    {
        Product::create([
            'sku' => 'IMAGE-FALLBACK', 'name' => 'Produk fallback', 'category' => 'Roti',
            'price' => 5000, 'stock_qty' => 4, 'is_active' => true, 'image_path' => 'products/missing-image.jpg',
        ]);
        $this->get(route('storefront.menu'))->assertOk()
            ->assertSee('data-store-image-fallback', false)
            ->assertSee('data-store-image', false);
    }

    public function test_customer_feedback_is_owned_and_admin_can_follow_up(): void
    {
        $first = $this->makeUser('customer');
        $second = User::create(['name' => 'Customer Kedua', 'email' => 'second@example.test', 'password' => 'password']);
        $second->role = 'customer';
        $second->is_active = true;
        $second->save();
        $firstCustomer = $this->makeCustomer($first, 'CUS-000101');
        $secondCustomer = $this->makeCustomer($second, 'CUS-000102');
        $otherOrder = Order::create([
            'order_number' => 'RK-OTHER-OWNER', 'customer_id' => $secondCustomer->id,
            'created_by' => $second->id, 'order_source' => Order::SOURCE_WEBSITE,
            'order_type' => Order::TYPE_TAKEAWAY, 'status' => Order::STATUS_PENDING,
            'subtotal' => 1000, 'discount_amount' => 0, 'total_amount' => 1000,
        ]);
        $otherFeedback = Feedback::create(['customer_id' => $secondCustomer->id, 'type' => Feedback::TYPE_REVIEW, 'message' => 'Feedback milik pelanggan lain', 'status' => Feedback::STATUS_NEW]);

        $this->actingAs($first)->get(route('customer.feedback'))->assertOk()->assertDontSee('Feedback milik pelanggan lain');
        $this->actingAs($first)->get(route('customer.orders.show', $otherOrder))->assertNotFound();
        $this->post(route('customer.feedback.store'), ['type' => Feedback::TYPE_SUGGESTION, 'message' => 'Mohon tambahkan pilihan roti gandum.'])
            ->assertRedirect(route('customer.feedback'));
        $this->assertDatabaseHas('feedback', ['customer_id' => $firstCustomer->id, 'type' => 'suggestion']);
        $this->actingAs($first)->post(route('customer.feedback.store'), ['type' => 'review', 'order_id' => $otherOrder->id, 'message' => 'Pesanan ini bukan milik akun saya.'])
            ->assertSessionHasErrors('order_id');

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->patch(route('admin.feedback.update', $otherFeedback), [
            'status' => Feedback::STATUS_RESOLVED,
            'response' => 'Terima kasih atas masukan Anda.',
        ])->assertRedirect();
        $this->assertDatabaseHas('feedback', ['id' => $otherFeedback->id, 'status' => Feedback::STATUS_RESOLVED, 'handled_by' => $admin->id]);
    }

    public function test_admin_can_create_and_validate_a_promotion(): void
    {
        $admin = $this->makeUser('admin');
        $payload = [
            'code' => 'rotikita10', 'name' => 'Diskon Uji', 'discount_type' => Promotion::TYPE_PERCENTAGE,
            'discount_value' => 10, 'min_order_amount' => 25000, 'starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->format('Y-m-d H:i:s'), 'usage_limit' => 10, 'is_active' => 1,
        ];

        $this->actingAs($admin)->post(route('admin.promotions.store'), $payload)->assertRedirect(route('admin.promotions'));
        $promotion = Promotion::query()->where('code', 'ROTIKITA10')->firstOrFail();
        $this->assertTrue($promotion->isCurrentlyValid());

        $this->actingAs($admin)->post(route('admin.promotions.store'), array_merge($payload, ['code' => 'TOO MUCH', 'discount_value' => 150]))
            ->assertSessionHasErrors('discount_value');
    }

    public function test_checkout_calculates_promotion_and_component_stock_for_combo_and_direct_product(): void
    {
        $user = $this->makeUser('customer');
        $customer = $this->makeCustomer($user, 'CUS-000201');
        $product = Product::create(['sku' => 'TEST-LOAF', 'name' => 'Roti Uji', 'category' => 'Roti', 'price' => 10000, 'stock_qty' => 10, 'is_active' => true]);
        $combo = Combo::create(['sku' => 'TEST-COMBO', 'name' => 'Combo Uji', 'price' => 15000, 'is_active' => true]);
        $combo->products()->sync([$product->id => ['quantity' => 2]]);
        $promotion = Promotion::create([
            'code' => 'HEMAT10', 'name' => 'Diskon Uji', 'discount_type' => Promotion::TYPE_PERCENTAGE,
            'discount_value' => 10, 'min_order_amount' => 0, 'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(), 'is_active' => true,
        ]);

        $this->actingAs($user)->withSession(['rotikita_cart' => ['product_'.$product->id => 1, 'combo_'.$combo->id => 2]])
            ->postJson(route('customer.checkout.quote'), ['promotion_code' => $promotion->code])
            ->assertOk()->assertJsonPath('total', '36000.00')->assertJsonPath('discount_cents', 400000);

        $this->withSession(['rotikita_cart' => ['product_'.$product->id => 1, 'combo_'.$combo->id => 2]])
            ->post(route('customer.checkout.store'), [
                'order_type' => Order::TYPE_TAKEAWAY, 'table_number' => 'STALE-TABLE',
                'payment_method' => Payment::METHOD_CASH, 'promotion_code' => $promotion->code,
            ])->assertRedirect();

        $order = Order::query()->where('customer_id', $customer->id)->firstOrFail();
        $this->assertSame('40000.00', $order->subtotal);
        $this->assertSame('4000.00', $order->discount_amount);
        $this->assertSame('36000.00', $order->total_amount);
        $this->assertNull($order->table_number);
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => Payment::STATUS_PENDING, 'amount' => '36000.00']);
        $this->assertDatabaseHas('stock_movements', ['order_id' => $order->id, 'product_id' => $product->id, 'quantity_change' => -5, 'stock_before' => 10, 'stock_after' => 5]);
        $this->assertSame(5, $product->fresh()->stock_qty);

        $this->actingAs($user)->patch(route('admin.orders.payment', $order), ['status' => Payment::STATUS_PAID])->assertForbidden();
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => Payment::STATUS_PENDING]);

        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->delete(route('admin.promotions.destroy', $promotion))->assertRedirect();
        $this->assertDatabaseHas('promotions', ['id' => $promotion->id, 'is_active' => false]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'promotion_code_snapshot' => 'HEMAT10']);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => Order::STATUS_CANCELLED])->assertRedirect();
        $this->assertSame(10, $product->fresh()->stock_qty);
        $this->assertSame(1, StockMovement::query()->where('order_id', $order->id)->where('movement_type', StockMovement::TYPE_SALE_REVERSAL)->count());
        $this->patch(route('admin.orders.status', $order), ['status' => Order::STATUS_CANCELLED])->assertSessionHasErrors('status');
        $this->assertSame(10, $product->fresh()->stock_qty);
    }

    public function test_admin_can_manage_combo_components(): void
    {
        $admin = $this->makeUser('admin');
        $product = Product::create(['sku' => 'COMBO-PRODUCT', 'name' => 'Produk Combo', 'category' => 'Roti', 'price' => 8000, 'stock_qty' => 20, 'is_active' => true]);
        $payload = [
            'sku' => 'combo-admin-test', 'name' => 'Paket Uji', 'price' => 12000, 'is_active' => 1,
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ];

        $this->actingAs($admin)->post(route('admin.combos.store'), $payload)->assertRedirect(route('admin.combos.index'));
        $combo = Combo::query()->where('sku', 'COMBO-ADMIN-TEST')->firstOrFail();
        $this->assertDatabaseHas('combo_items', ['combo_id' => $combo->id, 'product_id' => $product->id, 'quantity' => 2]);

        $payload['items'][0]['quantity'] = 3;
        $this->put(route('admin.combos.update', $combo), $payload)->assertRedirect(route('admin.combos.index'));
        $this->assertDatabaseHas('combo_items', ['combo_id' => $combo->id, 'product_id' => $product->id, 'quantity' => 3]);
    }

    public function test_checkout_combo_can_mix_product_categories_and_decrements_each_component_stock(): void
    {
        $user = $this->makeUser('customer');
        $this->makeCustomer($user, 'CUS-CROSS-CATEGORY');
        $bread = Product::create([
            'sku' => 'MIXED-ROTI-01', 'name' => 'Roti Kombo', 'category' => 'Roti',
            'price' => 12000, 'stock_qty' => 10, 'is_active' => true,
        ]);
        $cake = Product::create([
            'sku' => 'MIXED-CAKE-02', 'name' => 'Cake Kombo', 'category' => 'Cake & Tart',
            'price' => 30000, 'stock_qty' => 8, 'is_active' => true,
        ]);
        $combo = Combo::create(['sku' => 'MIXED-BAKERY-COMBO', 'name' => 'Paket Bakery', 'price' => 50000, 'is_active' => true]);
        $combo->products()->sync([
            $bread->id => ['quantity' => 2],
            $cake->id => ['quantity' => 3],
        ]);

        $this->actingAs($user)->withSession(['rotikita_cart' => ['combo_'.$combo->id => 2]])
            ->post(route('customer.checkout.store'), [
                'order_type' => Order::TYPE_TAKEAWAY,
                'payment_method' => Payment::METHOD_CASH,
            ])->assertRedirect();

        $order = Order::query()->whereHas('items', fn ($items) => $items->where('combo_id', $combo->id))->firstOrFail();
        $this->assertSame(6, $bread->fresh()->stock_qty);
        $this->assertSame(2, $cake->fresh()->stock_qty);
        $this->assertDatabaseHas('stock_movements', [
            'order_id' => $order->id, 'product_id' => $bread->id, 'quantity_change' => -4,
            'stock_before' => 10, 'stock_after' => 6,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'order_id' => $order->id, 'product_id' => $cake->id, 'quantity_change' => -6,
            'stock_before' => 8, 'stock_after' => 2,
        ]);
    }

    public function test_dine_in_requires_table_number_and_cash_rejects_payment_proof(): void
    {
        $user = $this->makeUser('customer');
        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'order_type' => Order::TYPE_DINE_IN,
            'payment_method' => Payment::METHOD_CASH,
        ])->assertSessionHasErrors('table_number');

        $this->actingAs($user)->post(route('customer.checkout.store'), [
            'order_type' => Order::TYPE_TAKEAWAY,
            'payment_method' => Payment::METHOD_CASH,
            'payment_proof' => UploadedFile::fake()->create('proof.pdf', 2, 'application/pdf'),
        ])->assertSessionHasErrors('payment_proof');
    }

    public function test_payment_proof_is_private_notifies_admin_and_paid_income_is_idempotent(): void
    {
        Storage::fake('local');
        $customerUser = $this->makeUser('customer');
        $customer = $this->makeCustomer($customerUser, 'CUS-PAYMENT-01');
        $admin = $this->makeUser('admin');
        $order = Order::create([
            'order_number' => 'RK-PAYMENT-TEST', 'customer_id' => $customer->id,
            'created_by' => $customerUser->id, 'order_source' => Order::SOURCE_WEBSITE,
            'order_type' => Order::TYPE_TAKEAWAY, 'customer_name_snapshot' => $customer->full_name,
            'status' => Order::STATUS_PENDING, 'subtotal' => 85000, 'discount_amount' => 0, 'total_amount' => 85000,
        ]);
        Payment::create(['order_id' => $order->id, 'method' => Payment::METHOD_QRIS, 'status' => Payment::STATUS_PENDING, 'amount' => 85000]);

        $this->actingAs($customerUser)->post(route('customer.orders.payment-proof.store', $order), [
            'payment_proof' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])->assertRedirect();
        $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => Payment::STATUS_AWAITING_VERIFICATION]);
        $this->assertSame(1, $admin->notifications()->count());
        $this->actingAs($customerUser)->get(route('admin.inbox'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.inbox'))->assertOk()->assertSee('RK-PAYMENT-TEST');

        $this->actingAs($admin)->patch(route('admin.orders.payment', $order), ['status' => Payment::STATUS_PAID])->assertRedirect();
        $paidAt = $order->payment()->firstOrFail()->paid_at;
        $this->assertNotNull($paidAt);
        $this->assertDatabaseHas('payment_ledger_entries', ['payment_id' => $order->payment->id, 'entry_type' => PaymentLedgerEntry::TYPE_PAID]);
        $this->assertSame(1, $admin->notifications()->where('type', PaymentStatusChanged::class)->count());
        $revenue = app(RevenueService::class);
        $this->assertSame(85000, (int) $revenue->total());
        $this->patch(route('admin.orders.payment', $order), ['status' => Payment::STATUS_PAID])->assertRedirect();
        $this->assertSame(1, $revenue->successfulCount());
        $this->patch(route('admin.orders.payment', $order), ['status' => Payment::STATUS_REFUNDED])->assertRedirect();
        $this->assertSame(0.0, (float) $revenue->total());
        $this->assertDatabaseHas('payment_ledger_entries', ['payment_id' => $order->payment->id, 'entry_type' => PaymentLedgerEntry::TYPE_REFUND]);
    }

    public function test_cart_addition_stays_on_current_page_and_quantity_can_be_changed_or_removed(): void
    {
        $product = Product::create(['sku' => 'CART-AJAX-01', 'name' => 'Roti Keranjang', 'category' => 'Roti', 'price' => 85000, 'stock_qty' => 6, 'is_active' => true]);
        $this->postJson(route('customer.cart.items.store'), ['type' => 'product', 'id' => $product->id, 'quantity' => 2, 'price' => 1])
            ->assertOk()->assertJsonPath('cart_count', 2)->assertJsonPath('message', 'Produk berhasil ditambahkan ke keranjang.');
        $this->get(route('customer.cart'))->assertOk()->assertSee('Roti Keranjang')->assertSee('170.000');
        $this->put(route('customer.cart.items.update', ['type' => 'product', 'id' => $product->id]), ['quantity' => 3])->assertRedirect(route('customer.cart'));
        $this->delete(route('customer.cart.items.destroy', ['type' => 'product', 'id' => $product->id]))->assertRedirect(route('customer.cart'));
        $this->get(route('customer.cart'))->assertOk()->assertSee('Keranjang masih kosong');
    }

    public function test_admin_payment_settings_persist_bank_and_official_qris_image(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get(route('admin.payment-settings.edit'))->assertOk()->assertSee('multipart/form-data', false);
        $this->put(route('admin.payment-settings.update'), [
            'bank_name' => 'Bank Roti', 'bank_account_name' => 'PT RotiKita', 'bank_account_number' => '1234567890',
            'qris_image' => UploadedFile::fake()->image('qris-roti-kita.png'),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $settings = PaymentSetting::query()->whereKey(1)->firstOrFail();
        $this->assertSame('Bank Roti', $settings->bank_name);
        $this->assertSame('PT RotiKita', $settings->bank_account_name);
        $this->assertSame('1234567890', $settings->bank_account_number);
        Storage::disk('public')->assertExists($settings->qris_image);
        $this->get(route('admin.payment-settings.edit'))->assertOk()->assertSee('Bank Roti')->assertSee($settings->qris_image);
        $oldQrisPath = $settings->qris_image;
        $oldQrisUrl = Storage::disk('public')->url($oldQrisPath);
        $this->get(route('admin.payment-settings.edit'))->assertSee($oldQrisUrl);
        $this->put(route('admin.payment-settings.update'), [
            'bank_name' => 'Bank Roti', 'bank_account_name' => 'PT RotiKita', 'bank_account_number' => '1234567890',
            'qris_image' => UploadedFile::fake()->create('invalid.txt', 2, 'text/plain'),
        ])->assertSessionHasErrors('qris_image');
        $this->assertSame($oldQrisPath, $settings->fresh()->qris_image);
        Storage::disk('public')->assertExists($oldQrisPath);

        $user = $this->makeUser('customer');
        $this->makeCustomer($user, 'CUS-PAYMENT-SETTING');
        $product = Product::create(['sku' => 'PAYMENT-CONFIG-01', 'name' => 'Roti Konfigurasi', 'category' => 'Roti', 'price' => 25000, 'stock_qty' => 5, 'is_active' => true]);
        $this->actingAs($user)->withSession(['rotikita_cart' => ['product_'.$product->id => 1]])
            ->get(route('customer.checkout'))->assertOk()->assertSee('QRIS resmi merchant RotiKita')->assertSee($oldQrisUrl)
            ->assertSee('QRIS statis tidak mengisi nominal otomatis')
            ->assertSee('Bank Roti')->assertSee('1234567890')->assertSee('PT RotiKita')
            ->assertSee('Pembayaran tunai dilakukan di kasir.');
        $this->post(route('customer.checkout.store'), ['order_type' => Order::TYPE_TAKEAWAY, 'payment_method' => Payment::METHOD_QRIS])
            ->assertRedirect();
        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame(Payment::STATUS_PENDING, $order->payment->status);
        $this->assertSame($settings->qris_image, $order->payment->payment_details['qris_image']);
        $this->assertSame(25000, (int) $order->payment->amount);
        $this->actingAs($admin)->put(route('admin.payment-settings.update'), [
            'bank_name' => 'Bank Roti', 'bank_account_name' => 'PT RotiKita', 'bank_account_number' => '1234567890',
            'qris_image' => UploadedFile::fake()->image('qris-roti-kita-baru.png'),
        ])->assertRedirect();
        Storage::disk('public')->assertExists($oldQrisPath);
        $this->actingAs($user);
        $this->get(route('customer.orders.show', $order))->assertOk()
            ->assertSee('QRIS resmi merchant RotiKita')->assertSee($oldQrisUrl)
            ->assertSee($order->order_number)->assertSee('25.000')->assertSee('Unggah bukti pembayaran');
        $this->withSession(['rotikita_cart' => ['product_'.$product->id => 1]])
            ->post(route('customer.checkout.store'), ['order_type' => Order::TYPE_TAKEAWAY, 'payment_method' => Payment::METHOD_BANK_TRANSFER])
            ->assertRedirect();
        $bankOrder = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('Bank Roti', $bankOrder->payment->payment_details['bank_name']);
        $this->assertSame('1234567890', $bankOrder->payment->payment_details['bank_account_number']);
        $this->get(route('customer.orders.show', $bankOrder))->assertOk()->assertSee('Bank Roti')->assertSee('PT RotiKita');
    }

    public function test_unconfigured_qris_and_incomplete_bank_transfer_are_rejected_server_side(): void
    {
        $user = $this->makeUser('customer');
        $this->makeCustomer($user, 'CUS-PAYMENT-INVALID');
        $product = Product::create(['sku' => 'PAYMENT-INVALID-01', 'name' => 'Roti Validasi', 'category' => 'Roti', 'price' => 25000, 'stock_qty' => 5, 'is_active' => true]);
        $this->actingAs($user)->withSession(['rotikita_cart' => ['product_'.$product->id => 1]])
            ->get(route('customer.checkout'))->assertOk()
            ->assertSee('value="bank_transfer"', false)->assertSee('value="qris"', false)
            ->assertDontSee('value="bank_transfer" disabled', false)->assertDontSee('value="qris" disabled', false)
            ->assertSee('Informasi rekening RotiKita belum dikonfigurasi')->assertSee('QRIS resmi RotiKita belum dikonfigurasi');
        $this->post(route('customer.checkout.store'), ['order_type' => Order::TYPE_TAKEAWAY, 'payment_method' => Payment::METHOD_QRIS])
            ->assertSessionHasErrors('payment_method');
        $this->post(route('customer.checkout.store'), ['order_type' => Order::TYPE_TAKEAWAY, 'payment_method' => Payment::METHOD_BANK_TRANSFER])
            ->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('orders', 0);

        PaymentSetting::create(['qris_image' => 'payment-config/missing-official-qris.png']);
        $this->get(route('customer.checkout'))->assertOk()
            ->assertSee('QRIS resmi RotiKita belum dikonfigurasi')
            ->assertDontSee('<img src=', false);
        $this->post(route('customer.checkout.store'), ['order_type' => Order::TYPE_TAKEAWAY, 'payment_method' => Payment::METHOD_QRIS])
            ->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('orders', 0);
    }

    private function makeUser(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role).' Test',
            'email' => $role.'@example.test',
            'password' => 'password',
        ]);
        $user->role = $role;
        $user->is_active = true;
        $user->save();

        return $user;
    }

    private function makeCustomer(User $user, string $code): Customer
    {
        return Customer::create([
            'user_id' => $user->id,
            'customer_code' => $code,
            'full_name' => $user->name,
            'contact_email' => $user->email,
        ]);
    }

    private function withAdminSeederPassword(string $password): void
    {
        putenv('ROTIKITA_ADMIN_PASSWORD='.$password);
        $_ENV['ROTIKITA_ADMIN_PASSWORD'] = $password;
        $_SERVER['ROTIKITA_ADMIN_PASSWORD'] = $password;
    }
}
