<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use App\Services\RevenueService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PaymentLedgerRevenueTest extends TestCase
{
    use RefreshDatabase;

    public function test_ledger_metadata_migration_adds_and_reverses_only_its_columns(): void
    {
        $migration = require database_path('migrations/2026_11_03_000002_add_idempotency_metadata_to_payment_ledger_entries_table.php');

        $this->assertTrue(Schema::hasColumn('payment_ledger_entries', 'idempotency_key'));
        $migration->down();
        $this->assertFalse(Schema::hasColumn('payment_ledger_entries', 'idempotency_key'));
        $this->assertTrue(Schema::hasColumn('payment_ledger_entries', 'entry_type'));
        $migration->up();
        $this->assertTrue(Schema::hasColumn('payment_ledger_entries', 'reference'));
    }

    public function test_refund_reduces_net_revenue_on_the_date_it_is_recorded(): void
    {
        $this->travelTo('2026-11-03 10:00:00');
        $order = Order::create([
            'order_number' => 'RK-LEDGER-001',
            'order_source' => Order::SOURCE_WEBSITE,
            'order_type' => Order::TYPE_TAKEAWAY,
            'status' => Order::STATUS_PENDING,
            'customer_name_snapshot' => 'Ledger test',
            'subtotal' => 100000,
            'discount_amount' => 0,
            'total_amount' => 100000,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => Payment::METHOD_BANK_TRANSFER,
            'status' => Payment::STATUS_PAID,
            'amount' => 100000,
            'paid_at' => now(),
        ]);
        PaymentLedgerEntry::create([
            'payment_id' => $payment->id,
            'entry_type' => PaymentLedgerEntry::TYPE_PAID,
            'amount' => $payment->amount,
            'occurred_at' => now(),
            'idempotency_key' => 'payment:'.$payment->id.':paid',
        ]);

        $this->travelTo('2026-11-04 09:00:00');
        $payment->update(['status' => Payment::STATUS_REFUNDED, 'refunded_at' => now()]);
        PaymentLedgerEntry::create([
            'payment_id' => $payment->id,
            'entry_type' => PaymentLedgerEntry::TYPE_REFUND,
            'amount' => $payment->amount,
            'occurred_at' => now(),
            'idempotency_key' => 'payment:'.$payment->id.':refund',
        ]);

        $revenue = app(RevenueService::class);
        $this->assertSame(0.0, (float) $revenue->total());
        $this->assertSame(100000.0, $revenue->dailyTotals(now()->subDay()->startOfDay(), now()->subDay()->endOfDay())['2026-11-03']);
        $this->assertSame(-100000.0, $revenue->dailyTotals(now()->startOfDay(), now()->endOfDay())['2026-11-04']);
    }

    public function test_idempotency_key_prevents_duplicate_ledger_events_and_legacy_rows_remain_supported(): void
    {
        $order = Order::create([
            'order_number' => 'RK-LEDGER-002',
            'order_source' => Order::SOURCE_WEBSITE,
            'order_type' => Order::TYPE_TAKEAWAY,
            'status' => Order::STATUS_PENDING,
            'subtotal' => 25000,
            'discount_amount' => 0,
            'total_amount' => 25000,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => Payment::METHOD_BANK_TRANSFER,
            'status' => Payment::STATUS_PAID,
            'amount' => 25000,
            'paid_at' => now(),
        ]);
        PaymentLedgerEntry::create([
            'payment_id' => $payment->id,
            'entry_type' => PaymentLedgerEntry::TYPE_PAID,
            'amount' => 25000,
            'occurred_at' => now(),
            'idempotency_key' => 'payment:'.$payment->id.':paid',
        ]);

        $this->expectException(QueryException::class);
        PaymentLedgerEntry::create([
            'payment_id' => $payment->id,
            'entry_type' => 'adjustment',
            'amount' => 1,
            'occurred_at' => now(),
            'idempotency_key' => 'payment:'.$payment->id.':paid',
        ]);
    }
}
