<?php

namespace Tests\Feature;

use App\Models\Combo;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminComboManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_reactivate_combo_with_an_inactive_component(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $product = Product::query()->create([
            'sku' => 'ROT-COMPONENT-01',
            'name' => 'Komponen Combo',
            'category' => 'Roti Manis',
            'price' => 15000,
            'stock_qty' => 30,
            'is_active' => false,
        ]);
        $combo = Combo::query()->create([
            'sku' => 'COMBO-TEST-01',
            'name' => 'Combo Uji',
            'description' => 'Combo untuk pengujian.',
            'price' => 25000,
            'is_active' => false,
        ]);
        $combo->products()->attach($product, ['quantity' => 1]);

        $response = $this->actingAs($admin)->patch(route('admin.combos.active', $combo));

        $response->assertSessionHasErrors('combo');
        $this->assertFalse($combo->fresh()->is_active);
    }
}
