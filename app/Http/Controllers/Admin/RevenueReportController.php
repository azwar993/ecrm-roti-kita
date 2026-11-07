<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\RevenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RevenueReportController extends Controller
{
    public function __invoke(Request $request, RevenueService $revenue): View
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:today,last7,this_month,last_month,custom'],
            'from' => ['nullable', 'required_if:period,custom', 'date'],
            'until' => ['nullable', 'required_if:period,custom', 'date', 'after_or_equal:from'],
        ]);
        $period = $data['period'] ?? 'this_month';
        [$from, $until] = match ($period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'last7' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            'custom' => [Carbon::parse($data['from'])->startOfDay(), Carbon::parse($data['until'])->endOfDay()],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
        abort_if($from->greaterThan($until), 422);

        $daily = $revenue->dailyTotals($from, $until);
        $transactions = $revenue->paidPayments($from, $until)->with('order')
            ->latest('paid_at')->paginate(15)->withQueryString();

        return view('admin.revenue-report', [
            'period' => $period, 'from' => $from, 'until' => $until,
            'totalRevenue' => $revenue->total($from, $until),
            'transactionCount' => $revenue->successfulCount($from, $until),
            'dailyRevenue' => collect($daily),
            'transactions' => $transactions,
        ]);
    }
}
