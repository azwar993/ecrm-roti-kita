<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\RevenueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueReportController extends Controller
{
    public function __invoke(Request $request, RevenueService $revenue): View
    {
        ['period' => $period, 'from' => $from, 'until' => $until, 'orderStatus' => $orderStatus] = $this->reportFilters($request);
        $daily = $revenue->dailyTotals($from, $until, $orderStatus);
        $transactions = $revenue->successfulPayments($from, $until, $orderStatus)
            ->latest('paid_at')->paginate(15)->withQueryString();
        $refunds = $revenue->refundedPayments($from, $until, $orderStatus)
            ->latest('id')->paginate(15, ['*'], 'refund_page')->withQueryString();

        return view('admin.revenue-report', [
            'period' => $period,
            'from' => $from,
            'until' => $until,
            'orderStatus' => $orderStatus,
            'orderStatuses' => $this->orderStatuses(),
            'totalRevenue' => $revenue->total($from, $until, $orderStatus),
            'paidTotal' => $revenue->paidTotal($from, $until, $orderStatus),
            'refundTotal' => $revenue->refundTotal($from, $until, $orderStatus),
            'transactionCount' => $revenue->successfulCount($from, $until, $orderStatus),
            'orderCount' => Order::query()->whereBetween('created_at', [$from, $until])
                ->when($orderStatus, fn (Builder $query) => $query->where('status', $orderStatus))->count(),
            'dailyRevenue' => collect($daily),
            'transactions' => $transactions,
            'refunds' => $refunds,
        ]);
    }

    public function print(Request $request, RevenueService $revenue): View
    {
        ['period' => $period, 'from' => $from, 'until' => $until, 'orderStatus' => $orderStatus] = $this->reportFilters($request);

        return view('admin.revenue-report-print', [
            'from' => $from,
            'until' => $until,
            'orderStatus' => $orderStatus,
            'orderStatusLabel' => $orderStatus ? str_replace('_', ' ', ucfirst($orderStatus)) : 'Semua status pesanan',
            'createdAt' => now(),
            'transactionCount' => $revenue->successfulCount($from, $until, $orderStatus),
            'orderCount' => Order::query()->whereBetween('created_at', [$from, $until])
                ->when($orderStatus, fn (Builder $query) => $query->where('status', $orderStatus))->count(),
            'paidTotal' => $revenue->paidTotal($from, $until, $orderStatus),
            'refundTotal' => $revenue->refundTotal($from, $until, $orderStatus),
            'totalRevenue' => $revenue->total($from, $until, $orderStatus),
            'transactions' => $revenue->successfulPayments($from, $until, $orderStatus)->orderBy('id')->lazy(500),
            'refunds' => $revenue->refundedPayments($from, $until, $orderStatus)->orderBy('id')->lazy(500),
            'period' => $period,
        ]);
    }

    public function export(Request $request, RevenueService $revenue): StreamedResponse
    {
        ['from' => $from, 'until' => $until, 'orderStatus' => $orderStatus] = $this->reportFilters($request);
        $filename = 'laporan-penjualan-'.$from->format('Ymd').'-'.$until->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($from, $until, $orderStatus, $revenue): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            $write = static function (array $row) use ($output): void {
                fputcsv($output, array_map(static function (mixed $value): string {
                    $value = (string) $value;

                    return preg_match('/^[=+\-@\t\r]/u', $value) === 1 ? "'".$value : $value;
                }, $row));
            };
            $write(['Laporan Penjualan RotiKita Bakery']);
            $write(['Periode', $from->toDateString(), $until->toDateString()]);
            $write(['Status pesanan', $orderStatus ?? 'Semua status']);
            $write(['Jenis event', 'Nomor order', 'Tanggal order', 'Tanggal event', 'Status order', 'Status pembayaran', 'Subtotal', 'Diskon', 'Total order', 'Nominal paid', 'Nominal refund', 'Net revenue']);

            $revenue->successfulPayments($from, $until, $orderStatus)->orderBy('id')->chunk(500, function ($payments) use ($write): void {
                foreach ($payments as $payment) {
                    $write($this->eventRow($payment, 'paid', (float) ($payment->period_paid_amount ?? $payment->amount), 0));
                }
            });
            $revenue->refundedPayments($from, $until, $orderStatus)->orderBy('id')->chunk(500, function ($payments) use ($write): void {
                foreach ($payments as $payment) {
                    $refundAmount = (float) ($payment->period_refund_amount ?? $payment->amount);
                    $write($this->eventRow($payment, 'refund', 0, $refundAmount));
                }
            });

            $paidTotal = $revenue->paidTotal($from, $until, $orderStatus);
            $refundTotal = $revenue->refundTotal($from, $until, $orderStatus);
            $write([]);
            $write(['Total order paid', $revenue->successfulCount($from, $until, $orderStatus)]);
            $write(['Total order', Order::query()->whereBetween('created_at', [$from, $until])
                ->when($orderStatus, fn (Builder $query) => $query->where('status', $orderStatus))->count()]);
            $write(['Total pembayaran', $paidTotal]);
            $write(['Total refund sah', $refundTotal]);
            $write(['Pendapatan bersih', $revenue->total($from, $until, $orderStatus)]);
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{period:string,from:Carbon,until:Carbon,orderStatus:?string} */
    private function reportFilters(Request $request): array
    {
        $valid = $request->validate([
            'period' => ['nullable', 'in:today,last7,this_month,last_month,custom'],
            'from' => ['nullable', 'required_if:period,custom', 'date'],
            'until' => ['nullable', 'required_if:period,custom', 'date', 'after_or_equal:from'],
            'order_status' => ['nullable', 'in:all,'.implode(',', $this->orderStatuses())],
        ]);
        $period = $valid['period'] ?? 'this_month';
        [$from, $until] = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'last7' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'custom' => [Carbon::parse($valid['from'])->startOfDay(), Carbon::parse($valid['until'])->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        abort_if($from->greaterThan($until), 422);

        return [
            'period' => $period,
            'from' => $from,
            'until' => $until,
            'orderStatus' => ($valid['order_status'] ?? 'all') === 'all' ? null : $valid['order_status'],
        ];
    }

    /** @return array<int,string> */
    private function orderStatuses(): array
    {
        return [
            Order::STATUS_PENDING,
            Order::STATUS_CONFIRMED,
            Order::STATUS_PREPARING,
            Order::STATUS_READY,
            Order::STATUS_COMPLETED,
            Order::STATUS_CANCELLED,
        ];
    }

    /** @return array<int,string|float> */
    private function eventRow(object $payment, string $eventType, float $paid, float $refund): array
    {
        $order = $payment->order;
        $date = $eventType === 'refund'
            ? ($payment->period_refund_at ?? $payment->refunded_at ?? $payment->verified_at ?? $payment->updated_at)
            : ($payment->period_paid_at ?? $payment->paid_at);

        return [
            $eventType,
            $order?->order_number ?? 'Order tidak tersedia',
            $order?->created_at?->toDateTimeString() ?? '',
            $date?->toDateTimeString() ?? '',
            $order?->status ?? '',
            $payment->status,
            $order?->subtotal ?? '',
            $order?->discount_amount ?? '',
            $order?->total_amount ?? '',
            $paid,
            $refund,
            $paid - $refund,
        ];
    }
}
