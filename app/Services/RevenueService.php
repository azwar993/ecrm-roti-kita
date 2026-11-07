<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class RevenueService
{
    public function total(?Carbon $from = null, ?Carbon $until = null): int|float|string
    {
        $paid = $this->ledgerTotal(PaymentLedgerEntry::TYPE_PAID, $from, $until)
            + $this->legacyPaymentTotal($from, $until);
        $refunds = $this->ledgerTotal(PaymentLedgerEntry::TYPE_REFUND, $from, $until)
            + $this->legacyRefundTotal($from, $until);

        return $paid - $refunds;
    }

    public function successfulCount(?Carbon $from = null, ?Carbon $until = null): int
    {
        $ledgerCount = $this->hasLedgerTable()
            ? PaymentLedgerEntry::query()->where('entry_type', PaymentLedgerEntry::TYPE_PAID)
                ->when($from, fn (Builder $query) => $query->where('occurred_at', '>=', $from))
                ->when($until, fn (Builder $query) => $query->where('occurred_at', '<=', $until))
                ->count()
            : 0;

        return $ledgerCount + $this->legacyPayments()
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->whereNotNull('paid_at')
            ->when($from, fn (Builder $query) => $query->where('paid_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('paid_at', '<=', $until))
            ->count();
    }

    public function paidPayments(?Carbon $from = null, ?Carbon $until = null): Builder
    {
        return Payment::query()->where('status', Payment::STATUS_PAID)->whereNotNull('paid_at')
            ->when($from, fn (Builder $query) => $query->where('paid_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('paid_at', '<=', $until));
    }

    public function dailyTotals(Carbon $from, Carbon $until): array
    {
        $daily = [];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $until->copy()->startOfDay()) as $date) {
            $daily[$date->toDateString()] = 0.0;
        }
        if ($this->hasLedgerTable()) {
            foreach ([PaymentLedgerEntry::TYPE_PAID => 1, PaymentLedgerEntry::TYPE_REFUND => -1] as $type => $sign) {
                PaymentLedgerEntry::query()->where('entry_type', $type)
                    ->whereBetween('occurred_at', [$from, $until])
                    ->selectRaw('DATE(occurred_at) as ledger_day, SUM(amount) as ledger_total')
                    ->groupByRaw('DATE(occurred_at)')
                    ->get()->each(function ($entry) use (&$daily, $sign): void {
                        $daily[$entry->ledger_day] = ($daily[$entry->ledger_day] ?? 0) + ($sign * (float) $entry->ledger_total);
                    });
            }
        }

        $this->legacyPayments()->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->whereNotNull('paid_at')->whereBetween('paid_at', [$from, $until])
            ->selectRaw('DATE(paid_at) as ledger_day, SUM(amount) as ledger_total')
            ->groupByRaw('DATE(paid_at)')
            ->get()->each(function ($entry) use (&$daily): void {
                $daily[$entry->ledger_day] = ($daily[$entry->ledger_day] ?? 0) + (float) $entry->ledger_total;
            });

        $refundDate = $this->refundDateExpression();
        $this->legacyPayments()->where('status', Payment::STATUS_REFUNDED)
            ->whereRaw($refundDate.' BETWEEN ? AND ?', [$from, $until])
            ->selectRaw('DATE('.$refundDate.') as ledger_day, SUM(amount) as ledger_total')
            ->groupByRaw('DATE('.$refundDate.')')
            ->get()->each(function ($entry) use (&$daily): void {
                $daily[$entry->ledger_day] = ($daily[$entry->ledger_day] ?? 0) - (float) $entry->ledger_total;
            });

        return $daily;
    }

    private function ledgerTotal(string $type, ?Carbon $from, ?Carbon $until): float
    {
        if (! $this->hasLedgerTable()) {
            return 0.0;
        }

        return (float) PaymentLedgerEntry::query()->where('entry_type', $type)
            ->when($from, fn (Builder $query) => $query->where('occurred_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('occurred_at', '<=', $until))
            ->sum('amount');
    }

    private function legacyPaymentTotal(?Carbon $from, ?Carbon $until): float
    {
        return (float) $this->legacyPayments()->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->whereNotNull('paid_at')
            ->when($from, fn (Builder $query) => $query->where('paid_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('paid_at', '<=', $until))
            ->sum('amount');
    }

    private function legacyRefundTotal(?Carbon $from, ?Carbon $until): float
    {
        $refundDate = $this->refundDateExpression();

        return (float) $this->legacyPayments()->where('status', Payment::STATUS_REFUNDED)
            ->when($from, fn (Builder $query) => $query->whereRaw($refundDate.' >= ?', [$from]))
            ->when($until, fn (Builder $query) => $query->whereRaw($refundDate.' <= ?', [$until]))
            ->sum('amount');
    }

    private function legacyPayments(): Builder
    {
        $query = Payment::query();

        return $this->hasLedgerTable() ? $query->whereDoesntHave('ledgerEntries') : $query;
    }

    private function hasLedgerTable(): bool
    {
        return Schema::hasTable('payment_ledger_entries');
    }

    private function refundDateExpression(): string
    {
        return Schema::hasColumn('payments', 'refunded_at')
            ? 'COALESCE(refunded_at, verified_at, updated_at)'
            : 'COALESCE(verified_at, updated_at)';
    }
}
