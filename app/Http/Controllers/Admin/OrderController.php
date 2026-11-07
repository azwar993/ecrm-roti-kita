<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\PaymentLedgerEntry;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Notifications\PaymentStatusChanged;
use App\Services\RevenueService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request, RevenueService $revenue): View
    {
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));
        $query = Order::query()->with(['customer', 'items', 'payment'])->latest('id');

        if ($status !== 'all' && in_array($status, [
            Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING,
            Order::STATUS_READY, Order::STATUS_COMPLETED, Order::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name_snapshot', 'like', "%{$search}%")
                    ->orWhere('pickup_code', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(15)->withQueryString();
        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY])->count();
        $completedOrders = Order::where('status', Order::STATUS_COMPLETED)->count();
        $todayRevenue = (float) $revenue->total(today()->startOfDay(), today()->endOfDay());

        return view('admin.orders', compact('orders', 'status', 'search', 'totalOrders', 'pendingOrders', 'completedOrders', 'todayRevenue'));
    }

    public function show(Order $order): View
    {
        $order->load(['customer', 'items.product', 'items.combo', 'payment', 'statusHistories.changedBy', 'stockMovements.product']);

        return view('admin.order-show', compact('order'));
    }

    public function receipt(Order $order): View
    {
        $order->load(['customer', 'items', 'payment']);

        return view('orders.receipt', ['order' => $order, 'isAdmin' => true]);
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([
                Order::STATUS_CONFIRMED, Order::STATUS_PREPARING, Order::STATUS_READY,
                Order::STATUS_COMPLETED, Order::STATUS_CANCELLED,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $order, $data): void {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $transitions = [
                Order::STATUS_PENDING => [Order::STATUS_CONFIRMED, Order::STATUS_CANCELLED],
                Order::STATUS_CONFIRMED => [Order::STATUS_PREPARING, Order::STATUS_CANCELLED],
                Order::STATUS_PREPARING => [Order::STATUS_READY, Order::STATUS_CANCELLED],
                Order::STATUS_READY => [Order::STATUS_COMPLETED, Order::STATUS_CANCELLED],
                Order::STATUS_COMPLETED => [],
                Order::STATUS_CANCELLED => [],
            ];
            if (! in_array($data['status'], $transitions[$lockedOrder->status] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status tersebut tidak diizinkan dari status saat ini.']);
            }

            if ($data['status'] === Order::STATUS_COMPLETED && $lockedOrder->payment()->value('status') !== Payment::STATUS_PAID) {
                throw ValidationException::withMessages(['status' => 'Konfirmasi pembayaran terlebih dahulu sebelum menyelesaikan pesanan.']);
            }

            if ($data['status'] === Order::STATUS_CANCELLED) {
                $saleMovements = StockMovement::query()->where('order_id', $lockedOrder->id)
                    ->where('movement_type', StockMovement::TYPE_SALE)->orderBy('product_id')->lockForUpdate()->get();

                $productIds = $saleMovements->pluck('product_id')->unique()->sort()->values();
                $products = Product::query()->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

                foreach ($saleMovements as $movement) {
                    $product = $products->get($movement->product_id);
                    if (! $product) {
                        continue;
                    }
                    $before = (int) $product->stock_qty;
                    $restore = abs((int) $movement->quantity_change);
                    $after = $before + $restore;
                    $product->stock_qty = $after;
                    $product->save();
                    StockMovement::create([
                        'product_id' => $product->id,
                        'order_id' => $lockedOrder->id,
                        'created_by' => $request->user()->id,
                        'movement_type' => StockMovement::TYPE_SALE_REVERSAL,
                        'quantity_change' => $restore,
                        'stock_before' => $before,
                        'stock_after' => $after,
                        'reason' => 'Pengembalian stok karena pesanan dibatalkan '.$lockedOrder->order_number,
                        'created_at' => now(),
                    ]);
                }
                $lockedOrder->cancelled_at = now();
            }

            $lockedOrder->status = $data['status'];
            $lockedOrder->handled_by = $request->user()->id;
            if ($data['status'] === Order::STATUS_CONFIRMED) {
                $lockedOrder->confirmed_at = now();
            }
            if ($data['status'] === Order::STATUS_COMPLETED) {
                $lockedOrder->completed_at = now();
            }
            $lockedOrder->save();

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'status' => $lockedOrder->status,
                'note' => $data['note'] ?? null,
                'changed_by' => $request->user()->id,
                'created_at' => now(),
            ]);
        }, 3);

        return redirect()->route('admin.orders.show', $order)->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function verifyPayment(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Payment::STATUS_PAID, Payment::STATUS_REJECTED, Payment::STATUS_FAILED, Payment::STATUS_REFUNDED])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $changed = DB::transaction(function () use ($request, $order, $data): bool {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->where('order_id', $lockedOrder->id)->lockForUpdate()->firstOrFail();
            if ($data['status'] === Payment::STATUS_PAID && $payment->status === Payment::STATUS_PAID) {
                return false;
            }
            $cashPending = $payment->method === Payment::METHOD_CASH && $payment->status === Payment::STATUS_PENDING;
            if ($data['status'] === Payment::STATUS_PAID && $payment->status !== Payment::STATUS_AWAITING_VERIFICATION && ! $cashPending) {
                throw ValidationException::withMessages(['status' => 'Pembayaran hanya dapat disetujui setelah bukti menunggu verifikasi.']);
            }
            if ($data['status'] === Payment::STATUS_REJECTED && $payment->status !== Payment::STATUS_AWAITING_VERIFICATION) {
                throw ValidationException::withMessages(['status' => 'Pembayaran hanya dapat ditolak setelah bukti menunggu verifikasi.']);
            }
            if ($data['status'] === Payment::STATUS_REFUNDED && $payment->status !== Payment::STATUS_PAID) {
                throw ValidationException::withMessages(['status' => 'Refund hanya bisa dicatat setelah pembayaran berstatus dibayar.']);
            }
            if ($lockedOrder->status === Order::STATUS_CANCELLED && $data['status'] === Payment::STATUS_PAID) {
                throw ValidationException::withMessages(['status' => 'Pesanan sudah dibatalkan; pembayaran tidak bisa ditandai lunas.']);
            }

            $payment->status = $data['status'];
            $payment->verified_by = $request->user()->id;
            $payment->verified_at = now();
            $payment->paid_at = $data['status'] === Payment::STATUS_PAID ? ($payment->paid_at ?? now()) : ($data['status'] === Payment::STATUS_REFUNDED ? $payment->paid_at : null);
            if (Schema::hasColumn('payments', 'refunded_at')) {
                $payment->refunded_at = $data['status'] === Payment::STATUS_REFUNDED ? now() : null;
            }
            $payment->notes = $data['notes'] ?? $payment->notes;
            $payment->rejection_reason = $data['status'] === Payment::STATUS_REJECTED ? ($data['notes'] ?? 'Bukti pembayaran belum dapat diverifikasi.') : null;
            $payment->save();

            if (Schema::hasTable('payment_ledger_entries') && $data['status'] === Payment::STATUS_PAID) {
                $payment->ledgerEntries()->firstOrCreate(
                    ['entry_type' => PaymentLedgerEntry::TYPE_PAID],
                    [
                        'recorded_by' => $request->user()->id,
                        'amount' => $payment->amount,
                        'occurred_at' => $payment->paid_at,
                        'reference' => $payment->reference_number,
                        'idempotency_key' => 'payment:'.$payment->id.':paid',
                        'description' => 'Pembayaran dikonfirmasi untuk '.$order->order_number,
                    ],
                );
            } elseif (Schema::hasTable('payment_ledger_entries') && $data['status'] === Payment::STATUS_REFUNDED) {
                $payment->ledgerEntries()->firstOrCreate(
                    ['entry_type' => PaymentLedgerEntry::TYPE_PAID],
                    [
                        'recorded_by' => $payment->verified_by,
                        'amount' => $payment->amount,
                        'occurred_at' => $payment->paid_at ?? $payment->verified_at,
                        'reference' => $payment->reference_number,
                        'idempotency_key' => 'payment:'.$payment->id.':paid',
                        'description' => 'Pembayaran dikonfirmasi untuk '.$order->order_number,
                    ],
                );
                $payment->ledgerEntries()->firstOrCreate(
                    ['entry_type' => PaymentLedgerEntry::TYPE_REFUND],
                    [
                        'recorded_by' => $request->user()->id,
                        'amount' => $payment->amount,
                        'occurred_at' => $payment->refunded_at,
                        'reference' => $payment->reference_number,
                        'idempotency_key' => 'payment:'.$payment->id.':refund',
                        'description' => 'Refund dicatat untuk '.$order->order_number,
                    ],
                );
            }

            return true;
        }, 3);

        if ($changed && in_array($data['status'], [Payment::STATUS_PAID, Payment::STATUS_REJECTED, Payment::STATUS_REFUNDED, Payment::STATUS_FAILED], true)) {
            $customerUserId = Customer::query()->whereKey($order->customer_id)->value('user_id');
            if ($customerUserId && $customerUser = User::query()->find($customerUserId)) {
                Notification::send($customerUser, new PaymentStatusChanged($order, $data['status']));
            }
            Notification::send(User::query()->where('role', 'admin')->where('is_active', true)->get(), new PaymentStatusChanged($order, $data['status']));
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Status pembayaran berhasil diperbarui.');
    }
}
