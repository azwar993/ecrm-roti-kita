<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRevenueReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_order_detail_renders_old_payments_without_snapshots_and_new_snapshots(): void
    {
        $admin = $this->admin();
        $legacy = $this->orderWithPayment('RK-DETAIL-LEGACY', Payment::STATUS_PENDING, null);
        $modern = $this->orderWithPayment('RK-DETAIL-SNAPSHOT', Payment::STATUS_AWAITING_VERIFICATION, [
            'bank_name' => 'Bank Snapshot', 'bank_account_name' => 'RotiKita',
            'bank_account_number' => '90001', 'instruction' => 'Transfer sesuai nominal order.',
        ]);

        $this->actingAs($admin)->get(route('admin.orders.show', $legacy))->assertOk()
            ->assertSee('RK-DETAIL-LEGACY')->assertSee('Snapshot rekening tidak tersedia');
        $this->get(route('admin.orders.show', $modern))->assertOk()
            ->assertSee('RK-DETAIL-SNAPSHOT')->assertSee('Bank Snapshot')->assertSee('90001');
    }

    public function test_revenue_report_and_dashboard_count_only_paid_payments_by_paid_at(): void
    {
        $admin = $this->admin();
        $this->orderWithPayment('RK-REPORT-PAID', Payment::STATUS_PAID, null, 42000, now());
        $this->orderWithPayment('RK-REPORT-PENDING', Payment::STATUS_PENDING, null, 22000, now());
        $this->orderWithPayment('RK-REPORT-REFUND', Payment::STATUS_REFUNDED, null, 70000, now());

        $this->actingAs($admin)->get(route('admin.reports.revenue', ['period' => 'today']))
            ->assertOk()->assertSee('Rp 42.000')->assertSee('RK-REPORT-PAID')
            ->assertDontSee('RK-REPORT-PENDING')->assertDontSee('RK-REPORT-REFUND');
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Total pendapatan terkonfirmasi')->assertSee('Menunggu pembayaran');
    }

    private function admin(): User
    {
        $user = User::create(['name' => 'Admin Revenue', 'email' => 'admin-revenue@example.test', 'password' => 'password']);
        $user->role = 'admin';
        $user->is_active = true;
        $user->save();
        return $user;
    }

    private function orderWithPayment(string $number, string $status, ?array $details, int $amount = 50000, mixed $paidAt = null): Order
    {
        $order = Order::create([
            'order_number' => $number, 'order_source' => Order::SOURCE_WEBSITE,
            'order_type' => Order::TYPE_TAKEAWAY, 'status' => Order::STATUS_PENDING,
            'customer_name_snapshot' => 'Customer Report', 'subtotal' => $amount,
            'discount_amount' => 0, 'total_amount' => $amount,
        ]);
        Payment::create([
            'order_id' => $order->id, 'method' => Payment::METHOD_BANK_TRANSFER,
            'status' => $status, 'amount' => $amount, 'paid_at' => $paidAt,
            'payment_details' => $details,
        ]);
        return $order;
    }
}
