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
    public function total(?Carbon $from = null, ?Carbon $until = null, ?string $orderStatus = null): int|float|string
    {
        return $this->paidTotal($from, $until, $orderStatus) - $this->refundTotal($from, $until, $orderStatus);
    }

    public function paidTotal(?Carbon $from = null, ?Carbon $until = null, ?string $orderStatus = null): float
    {
        return $this->ledgerTotal(PaymentLedgerEntry::TYPE_PAID, $from, $until, $orderStatus)
            + $this->legacyPaymentTotal($from, $until, $orderStatus);
    }

    public function refundTotal(?Carbon $from = null, ?Carbon $until = null, ?string $orderStatus = null): float
    {
        return $this->ledgerTotal(PaymentLedgerEntry::TYPE_REFUND, $from, $until, $orderStatus)
            + $this->legacyRefundTotal($from, $until, $orderStatus);
    }

    public function successfulCount(?Carbon $from = null, ?Carbon $until = null, ?string $orderStatus = null): int
    {
        $ledgerCount = $this->hasLedgerTable()
            ? PaymentLedgerEntry::query()->where('entry_type', PaymentLedgerEntry::TYPE_PAID)
                ->when($from, fn (Builder $query) => $query->where('occurred_at', '>=', $from))
                ->when($until, fn (Builder $query) => $query->where('occurred_at', '<=', $until))
                ->when($orderStatus, fn (Builder $query) => $query->whereHas('payment.order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)))
                ->count()
            : 0;

        return $ledgerCount + $this->legacyPayments($orderStatus)
            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->whereNotNull('paid_at')
            ->when($from, fn (Builder $query) => $query->where('paid_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('paid_at', '<=', $until))
            ->count();
    }

    public function paidPayments(?Carbon $from = null, ?Carbon $until = null, ?string $orderStatus = null): Builder
    {
        return Payment::query()->where('status', Payment::STATUS_PAID)->whereNotNull('paid_at')
            ->when($from, fn (Builder $query) => $query->where('paid_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('paid_at', '<=', $until))
            ->when($orderStatus, fn (Builder $query) => $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)));
    }

    public function successfulPayments(Carbon $from, Carbon $until, ?string $orderStatus = null): Builder
    {
        if (! $this->hasLedgerTable()) {
            return Payment::query()->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
                ->whereBetween('paid_at', [$from, $until])
                ->when($orderStatus, fn (Builder $query) => $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)))
                ->with('order');
        }

        $paidEvents = fn (Builder $query) => $query->where('entry_type', PaymentLedgerEntry::TYPE_PAID)
            ->whereBetween('occurred_at', [$from, $until]);

        return Payment::query()
            ->where(function (Builder $query) use ($paidEvents, $from, $until): void {
                $query->whereHas('ledgerEntries', $paidEvents)
                    ->orWhere(function (Builder $legacy) use ($from, $until): void {
                        $legacy->whereDoesntHave('ledgerEntries')
                            ->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
                            ->whereBetween('paid_at', [$from, $until]);
                    });
            })
            ->when($orderStatus, fn (Builder $query) => $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)))
            ->with('order')
            ->withMin(['ledgerEntries as period_paid_at' => $paidEvents], 'occurred_at')
            ->withSum(['ledgerEntries as period_paid_amount' => $paidEvents], 'amount');
    }

    public function refundedPayments(Carbon $from, Carbon $until, ?string $orderStatus = null): Builder
    {
        $refundEvents = fn (Builder $query) => $query->where('entry_type', PaymentLedgerEntry::TYPE_REFUND)
            ->whereBetween('occurred_at', [$from, $until]);
        $refundDate = $this->refundDateExpression();

        if (! $this->hasLedgerTable()) {
            return Payment::query()->where('status', Payment::STATUS_REFUNDED)
                ->whereRaw($refundDate.' BETWEEN ? AND ?', [$from, $until])
                ->when($orderStatus, fn (Builder $query) => $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)))
                ->with('order');
        }

        return Payment::query()
            ->where(function (Builder $query) use ($refundEvents, $refundDate, $from, $until): void {
                $query->whereHas('ledgerEntries', $refundEvents)
                    ->orWhere(function (Builder $legacy) use ($refundDate, $from, $until): void {
                        $legacy->whereDoesntHave('ledgerEntries')
                            ->where('status', Payment::STATUS_REFUNDED)
                            ->whereRaw($refundDate.' BETWEEN ? AND ?', [$from, $until]);
                    });
            })
            ->when($orderStatus, fn (Builder $query) => $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)))
            ->with('order')
            ->withMin(['ledgerEntries as period_refund_at' => $refundEvents], 'occurred_at')
            ->withSum(['ledgerEntries as period_refund_amount' => $refundEvents], 'amount');
    }

    public function dailyTotals(Carbon $from, Carbon $until, ?string $orderStatus = null): array
    {
        $daily = [];
        foreach (CarbonPeriod::create($from->copy()->startOfDay(), $until->copy()->startOfDay()) as $date) {
            $daily[$date->toDateString()] = 0.0;
        }
        if ($this->hasLedgerTable()) {
            foreach ([PaymentLedgerEntry::TYPE_PAID => 1, PaymentLedgerEntry::TYPE_REFUND => -1] as $type => $sign) {
                PaymentLedgerEntry::query()->where('entry_type', $type)
                    ->whereBetween('occurred_at', [$from, $until])
                    ->when($orderStatus, fn (Builder $query) => $query->whereHas('payment.order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)))
                    ->selectRaw('DATE(occurred_at) as ledger_day, SUM(amount) as ledger_total')
                    ->groupByRaw('DATE(occurred_at)')
                    ->get()->each(function ($entry) use (&$daily, $sign): void {
                        $daily[$entry->ledger_day] = ($daily[$entry->ledger_day] ?? 0) + ($sign * (float) $entry->ledger_total);
                    });
            }
        }

        $this->legacyPayments($orderStatus)->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->whereNotNull('paid_at')->whereBetween('paid_at', [$from, $until])
            ->selectRaw('DATE(paid_at) as ledger_day, SUM(amount) as ledger_total')
            ->groupByRaw('DATE(paid_at)')
            ->get()->each(function ($entry) use (&$daily): void {
                $daily[$entry->ledger_day] = ($daily[$entry->ledger_day] ?? 0) + (float) $entry->ledger_total;
            });

        $refundDate = $this->refundDateExpression();
        $this->legacyPayments($orderStatus)->where('status', Payment::STATUS_REFUNDED)
            ->whereRaw($refundDate.' BETWEEN ? AND ?', [$from, $until])
            ->selectRaw('DATE('.$refundDate.') as ledger_day, SUM(amount) as ledger_total')
            ->groupByRaw('DATE('.$refundDate.')')
            ->get()->each(function ($entry) use (&$daily): void {
                $daily[$entry->ledger_day] = ($daily[$entry->ledger_day] ?? 0) - (float) $entry->ledger_total;
            });

        return $daily;
    }

    private function ledgerTotal(string $type, ?Carbon $from, ?Carbon $until, ?string $orderStatus = null): float
    {
        if (! $this->hasLedgerTable()) {
            return 0.0;
        }

        return (float) PaymentLedgerEntry::query()->where('entry_type', $type)
            ->when($from, fn (Builder $query) => $query->where('occurred_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('occurred_at', '<=', $until))
            ->when($orderStatus, fn (Builder $query) => $query->whereHas('payment.order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)))
            ->sum('amount');
    }

    private function legacyPaymentTotal(?Carbon $from, ?Carbon $until, ?string $orderStatus = null): float
    {
        return (float) $this->legacyPayments($orderStatus)->whereIn('status', [Payment::STATUS_PAID, Payment::STATUS_REFUNDED])
            ->whereNotNull('paid_at')
            ->when($from, fn (Builder $query) => $query->where('paid_at', '>=', $from))
            ->when($until, fn (Builder $query) => $query->where('paid_at', '<=', $until))
            ->sum('amount');
    }

    private function legacyRefundTotal(?Carbon $from, ?Carbon $until, ?string $orderStatus = null): float
    {
        $refundDate = $this->refundDateExpression();

        return (float) $this->legacyPayments($orderStatus)->where('status', Payment::STATUS_REFUNDED)
            ->when($from, fn (Builder $query) => $query->whereRaw($refundDate.' >= ?', [$from]))
            ->when($until, fn (Builder $query) => $query->whereRaw($refundDate.' <= ?', [$until]))
            ->sum('amount');
    }

    private function legacyPayments(?string $orderStatus = null): Builder
    {
        $query = Payment::query();

        $query->when($this->hasLedgerTable(), fn (Builder $query) => $query->whereDoesntHave('ledgerEntries'));

        return $query->when($orderStatus, fn (Builder $query) => $query->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', $orderStatus)));
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
