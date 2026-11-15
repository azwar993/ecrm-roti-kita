<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
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
            ->assertDontSee('RK-REPORT-PENDING')->assertSee('RK-REPORT-REFUND');
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Pendapatan hari ini')->assertSee('Menunggu pembayaran')
            ->assertSee('Tren pendapatan')->assertSee('Akumulasi periode');
        $this->get(route('admin.dashboard', ['period' => '30']))
            ->assertOk()->assertSee('value="30" selected', false)->assertSee('30 hari');
    }

    public function test_admin_can_download_filtered_csv_and_formula_like_order_number_is_neutralized(): void
    {
        $admin = $this->admin();
        $paid = $this->orderWithPayment('=2+2', Payment::STATUS_PAID, null, 42000, now());
        $this->orderWithPayment('RK-CSV-PENDING', Payment::STATUS_PENDING, null, 22000, now());
        $response = $this->actingAs($admin)->get(route('admin.reports.revenue.export', ['period' => 'today']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('filename=laporan-penjualan-'.now()->format('Ymd').'-'.now()->format('Ymd').'.csv', $response->headers->get('content-disposition'));
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString("'=2+2", $content);
        $this->assertStringContainsString($paid->order_number, $content);
        $this->assertStringNotContainsString('RK-CSV-PENDING', $content);
        $this->assertStringContainsString('Pendapatan bersih', $content);
    }

    public function test_customer_and_guest_cannot_download_revenue_csv(): void
    {
        $customer = User::create(['name' => 'Customer', 'email' => 'customer-report@example.test', 'password' => 'password']);
        $customer->role = 'customer';
        $customer->is_active = true;
        $customer->save();

        $this->get(route('admin.reports.revenue.export'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.revenue.print'))->assertRedirect(route('login'));
        $this->actingAs($customer)->get(route('admin.reports.revenue.export'))->assertForbidden();
        $this->get(route('admin.reports.revenue.print'))->assertForbidden();
    }

    public function test_order_status_filter_is_applied_to_revenue_summary_and_details(): void
    {
        $admin = $this->admin();
        $completed = $this->orderWithPayment('RK-STATUS-COMPLETED', Payment::STATUS_PAID, null, 42000, now());
        $completed->update(['status' => Order::STATUS_COMPLETED]);
        $cancelled = $this->orderWithPayment('RK-STATUS-CANCELLED', Payment::STATUS_REFUNDED, null, 70000, now());
        $cancelled->update(['status' => Order::STATUS_CANCELLED]);
        $cancelledPayment = $cancelled->payment()->firstOrFail();
        foreach ([PaymentLedgerEntry::TYPE_PAID, PaymentLedgerEntry::TYPE_REFUND] as $entryType) {
            PaymentLedgerEntry::create([
                'payment_id' => $cancelledPayment->id,
                'entry_type' => $entryType,
                'amount' => $cancelledPayment->amount,
                'occurred_at' => now(),
                'idempotency_key' => 'payment:'.$cancelledPayment->id.':'.$entryType,
            ]);
        }

        $this->actingAs($admin)->get(route('admin.reports.revenue', ['period' => 'today', 'order_status' => Order::STATUS_COMPLETED]))
            ->assertOk()->assertSee('RK-STATUS-COMPLETED')->assertDontSee('RK-STATUS-CANCELLED')
            ->assertSee('42.000');
        $csv = $this->get(route('admin.reports.revenue.export', ['period' => 'today', 'order_status' => Order::STATUS_CANCELLED]))
            ->assertOk()->streamedContent();
        $this->assertStringContainsString('RK-STATUS-CANCELLED', $csv);
        $this->assertStringNotContainsString('RK-STATUS-COMPLETED', $csv);
        $this->assertStringContainsString('Total refund sah', $csv);
        $this->assertSame(2, substr_count($csv, 'RK-STATUS-CANCELLED'));
    }

    public function test_print_view_contains_all_filtered_payments_beyond_page_size(): void
    {
        $admin = $this->admin();
        for ($index = 0; $index < 19; $index++) {
            $this->orderWithPayment('RK-PRINT-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT), Payment::STATUS_PAID, null, 1000, now());
        }

        $this->actingAs($admin)->get(route('admin.reports.revenue.print', ['period' => 'today']))
            ->assertOk()->assertSee('RK-PRINT-00')->assertSee('RK-PRINT-18')
            ->assertSee('Laporan Penjualan')->assertSee('Pendapatan bersih');
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
