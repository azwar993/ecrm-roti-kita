<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->first();
        $orders = $customer
            ? $customer->orders()->with('payment')->latest('id')->limit(5)->get()
            : collect();

        return view('customer.dashboard', [
            'totalOrders' => $customer?->orders()->count() ?? 0,
            'activeOrders' => $customer?->orders()->whereIn('status', [
                Order::STATUS_PENDING, Order::STATUS_CONFIRMED,
                Order::STATUS_PREPARING, Order::STATUS_READY,
            ])->count() ?? 0,
            'feedbackCount' => $customer?->feedback()->count() ?? 0,
            'recentOrders' => $orders,
        ]);
    }
}
