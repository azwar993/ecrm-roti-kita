<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->first();
        $orders = $customer
            ? $customer->orders()->with(['payment', 'items'])->latest('id')->limit(5)->get()
            : collect();
        $activeOrder = $customer
            ? $customer->orders()
                ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY])
                ->with(['payment', 'items', 'statusHistories'])
                ->latest('id')
                ->first()
            : null;

        return view('customer.dashboard', [
            'totalOrders' => $customer?->orders()->count() ?? 0,
            'activeOrders' => $customer?->orders()->whereIn('status', [
                Order::STATUS_PENDING, Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING, Order::STATUS_READY,
            ])->count() ?? 0,
            'feedbackCount' => $customer?->feedback()->count() ?? 0,
            'recentOrders' => $orders,
            'activeOrder' => $activeOrder,
            'featuredProducts' => Product::query()
                ->where('is_active', true)
                ->where('stock_qty', '>', 0)
                ->latest('id')
                ->limit(4)
                ->get(),
        ]);
    }
}
