<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromotionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotions_page_renders_empty_and_filtered_results(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->actingAs($admin)->get(route('admin.promotions'))->assertOk()->assertSee('Belum ada promosi');

        $promotion = Promotion::create([
            'code' => 'ROTIKITA10', 'name' => 'Diskon Roti', 'discount_type' => 'percentage',
            'discount_value' => 10, 'min_order_amount' => 0, 'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(), 'is_active' => true,
        ]);
        $this->get(route('admin.promotions'))->assertOk()->assertSee($promotion->name);
        $this->get(route('admin.promotions', ['status' => 'active', 'q' => 'ROTIKITA']))
            ->assertOk()->assertSee($promotion->code);
        $this->get(route('admin.promotions', ['status' => 'inactive', 'q' => 'ROTIKITA']))
            ->assertOk()->assertDontSee($promotion->name);
    }

    public function test_admin_can_create_edit_toggle_and_delete_unused_promotion(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $payload = [
            'code' => 'HEMAT10', 'name' => 'Hemat 10', 'discount_type' => 'percentage',
            'discount_value' => 10, 'min_order_amount' => 0, 'starts_at' => now()->toDateTimeString(),
            'ends_at' => now()->addDays(7)->toDateTimeString(), 'is_active' => 1,
        ];
        $this->actingAs($admin)->post(route('admin.promotions.store'), $payload)->assertRedirect(route('admin.promotions'));
        $promotion = Promotion::where('code', 'HEMAT10')->firstOrFail();
        $this->put(route('admin.promotions.update', $promotion), array_merge($payload, ['name' => 'Hemat Spesial']))
            ->assertRedirect(route('admin.promotions'));
        $this->patch(route('admin.promotions.active', $promotion))->assertSessionHasNoErrors();
        $this->delete(route('admin.promotions.destroy', $promotion))->assertRedirect(route('admin.promotions'));
        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }
}
