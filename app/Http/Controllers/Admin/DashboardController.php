<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Services\RevenueService;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(RevenueService $revenue): View
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $pendingVerification = Payment::query()->where('status', Payment::STATUS_AWAITING_VERIFICATION);
        $paidOrders = Order::query()->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_PAID)->whereBetween('paid_at', [$monthStart, $monthEnd]));
        $buyers = (clone $paidOrders)->whereNotNull('customer_id')->distinct()->count('customer_id');
        $repeatBuyers = (clone $paidOrders)->whereNotNull('customer_id')->select('customer_id')->groupBy('customer_id')->havingRaw('COUNT(orders.id) >= 2')->get()->count();
        $paidOrderCount = (clone $paidOrders)->count();
        $paidOrderAmount = (clone $paidOrders)->join('payments', 'payments.order_id', '=', 'orders.id')->sum('payments.amount');

        return view('admin.dashboard', [
            'customerCount' => Customer::query()->count(),
            'monthOrderCount' => Order::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'monthRevenue' => $revenue->total($monthStart, $monthEnd),
            'totalRevenue' => $revenue->total(),
            'monthSuccessfulTransactions' => $revenue->successfulCount($monthStart, $monthEnd),
            'pendingVerificationCount' => (clone $pendingVerification)->count(),
            'pendingVerificationAmount' => (clone $pendingVerification)->sum('amount'),
            'pendingPaymentCount' => Payment::query()->where('status', Payment::STATUS_PENDING)->count(),
            'feedbackCount' => Feedback::query()->count(),
            'unresolvedFeedbackCount' => Feedback::query()->whereIn('status', [Feedback::STATUS_NEW, Feedback::STATUS_IN_PROGRESS])->count(),
            'pendingOrders' => Order::query()->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY])->count(),
            'lowStockCount' => Product::query()->where('is_active', true)->where('stock_qty', '<=', 5)->count(),
            'recentOrders' => Order::query()->with(['customer', 'items', 'payment'])->latest('id')->limit(5)->get(),
            'monthActiveCustomers' => $buyers,
            'monthRepeatPurchaseRate' => $buyers > 0 ? round($repeatBuyers / $buyers * 100, 1) : 0,
            'monthAverageOrderValue' => $paidOrderCount > 0 ? (float) $paidOrderAmount / $paidOrderCount : 0,
            'averageFeedbackRating' => Feedback::query()->whereNotNull('rating')->whereBetween('created_at', [$monthStart, $monthEnd])->avg('rating'),
            'openSupportTicketCount' => Schema::hasTable('support_tickets') ? SupportTicket::query()->whereIn('status', ['open', 'in_progress', 'waiting_customer'])->count() : 0,
        ]);
    }
}
