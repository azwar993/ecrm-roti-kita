<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Feedback;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');
        $query = Customer::query()
            ->with('user:id,name,email')
            ->withCount('orders')
            ->withCount(['orders as completed_orders_count' => fn (Builder $orders) => $orders->where('status', Order::STATUS_COMPLETED)])
            ->withSum(['orders as completed_spend' => fn (Builder $orders) => $orders->where('status', Order::STATUS_COMPLETED)], 'total_amount')
            ->withMax('orders', 'created_at')
            ->latest('id');

        if ($search !== '') {
            $query->where(function (Builder $customers) use ($search): void {
                $customers->where('full_name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        match ($filter) {
            'account' => $query->whereNotNull('user_id'),
            'no_account' => $query->whereNull('user_id'),
            'consent_yes' => $query->where('email_marketing_consent', true),
            'consent_no' => $query->where('email_marketing_consent', false),
            'with_orders' => $query->has('orders'),
            'without_orders' => $query->doesntHave('orders'),
            default => null,
        };

        return view('admin.customers', [
            'customers' => $query->paginate(12)->withQueryString(),
            'search' => $search,
            'filter' => $filter,
            'totalCustomers' => Customer::query()->count(),
            'customersWithOrders' => Customer::query()->has('orders')->count(),
            'newThisMonth' => Customer::query()->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
        ]);
    }

    public function show(Customer $customer): View
    {
        $customer->load('user:id,name,email');
        $orders = $customer->orders()->with(['items', 'payment'])->latest('id')->paginate(10);
        $feedback = $customer->feedback()->with('order')->latest('id')->limit(5)->get();
        $completedSpend = $customer->orders()->where('status', Order::STATUS_COMPLETED)->sum('total_amount');

        return view('admin.customer-show', compact('customer', 'orders', 'feedback', 'completedSpend'));
    }
}
