<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\User;
use App\Notifications\PaymentProofSubmitted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->firstOrFail();
        $orders = Order::query()->where('customer_id', $customer->id)
            ->with(['items', 'payment'])
            ->latest('id')->paginate(10);

        return view('customer.orders', compact('orders'));
    }

    public function show(Request $request, Order $order): View
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless((int) $order->customer_id === (int) $customer->id, 404);
        $order->load(['items.product', 'items.combo', 'payment', 'statusHistories']);

        $settings = PaymentSetting::query()->whereKey(1)->first();
        $paymentDetails = $order->payment?->payment_details ?? [];
        $hasQrisSnapshot = array_key_exists('qris_image', $paymentDetails);
        $qrisImage = $hasQrisSnapshot
            ? $paymentDetails['qris_image']
            : ($settings?->qris_image ?: config('rotikita-payment.qris_image'));
        $qrisConfigured = is_string($qrisImage) && trim($qrisImage) !== '' && Storage::disk('public')->exists(ltrim($qrisImage, '/'));
        $qrisImageUrl = $qrisConfigured ? Storage::disk('public')->url(ltrim($qrisImage, '/')) : null;
        $bankDetails = [
            'bank_name' => $paymentDetails['bank_name'] ?? ($settings ? $settings->bank_name : config('rotikita-payment.bank_name')),
            'bank_account_name' => $paymentDetails['bank_account_name'] ?? ($settings ? $settings->bank_account_name : config('rotikita-payment.bank_account_name')),
            'bank_account_number' => $paymentDetails['bank_account_number'] ?? ($settings ? $settings->bank_account_number : config('rotikita-payment.bank_account_number')),
        ];

        return view('customer.order-show', ['order' => $order, 'qrisImage' => $qrisImage, 'qrisConfigured' => $qrisConfigured, 'qrisImageUrl' => $qrisImageUrl, 'bankDetails' => $bankDetails]);
    }

    public function receipt(Request $request, Order $order): View
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless((int) $order->customer_id === (int) $customer->id, 404);
        $order->load(['items', 'payment', 'customer']);

        return view('orders.receipt', ['order' => $order, 'isAdmin' => false]);
    }

    public function uploadPaymentProof(Request $request, Order $order): RedirectResponse
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless((int) $order->customer_id === (int) $customer->id, 404);
        $data = $request->validate(['payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096']]);
        $path = $data['payment_proof']->store('payment-proofs', 'local');
        $oldProofPath = null;

        try {
            $resubmitted = DB::transaction(function () use ($order, $path, &$oldProofPath): bool {
                $payment = Payment::query()->where('order_id', $order->id)->lockForUpdate()->firstOrFail();
                if ($payment->method === Payment::METHOD_CASH) {
                    throw ValidationException::withMessages(['payment_proof' => 'Pembayaran tunai tidak memerlukan bukti transfer.']);
                }
                if (! in_array($payment->status, [Payment::STATUS_PENDING, Payment::STATUS_REJECTED, Payment::STATUS_FAILED], true)) {
                    throw ValidationException::withMessages(['payment_proof' => 'Bukti hanya dapat dikirim untuk pembayaran yang belum dibayar atau ditolak.']);
                }
                $resubmitted = in_array($payment->status, [Payment::STATUS_REJECTED, Payment::STATUS_FAILED], true);
                $oldProofPath = $payment->proof_path;
                $payment->proof_path = $path;
                $payment->status = Payment::STATUS_AWAITING_VERIFICATION;
                $payment->rejection_reason = null;
                $payment->notes = 'Customer mengirim bukti pembayaran; menunggu verifikasi admin.';
                $payment->save();

                return $resubmitted;
            }, 3);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }
        if ($oldProofPath) {
            Storage::disk('local')->delete($oldProofPath);
        }

        $admins = User::query()->where('role', 'admin')->where('is_active', true)->get();
        Notification::send($admins, new PaymentProofSubmitted($order, $resubmitted));

        return back()->with('success', 'Bukti pembayaran berhasil dikirim dan sedang menunggu verifikasi admin.');
    }

    public function paymentProof(Request $request, Order $order): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->firstOrFail();
        abort_unless((int) $order->customer_id === (int) $customer->id, 404);
        $payment = $order->payment;
        abort_unless($payment?->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return response()->file(Storage::disk('local')->path($payment->proof_path));
    }
}
