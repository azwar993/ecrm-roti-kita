<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->first();
        $feedbacks = $customer
            ? $customer->feedback()->with('order')->latest('id')->paginate(10)
            : collect();
        $orders = $customer?->orders()->latest('id')->limit(50)->get(['id', 'order_number']) ?? collect();

        return view('customer.feedback', compact('feedbacks', 'orders', 'customer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::query()->where('user_id', $request->user()->id)->firstOrFail();
        $data = $request->validate([
            'type' => ['required', Rule::in([Feedback::TYPE_COMPLAINT, Feedback::TYPE_SUGGESTION, Feedback::TYPE_REVIEW])],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        if (! empty($data['order_id']) && ! $customer->orders()->whereKey($data['order_id'])->exists()) {
            throw ValidationException::withMessages(['order_id' => 'Pesanan yang dipilih tidak ditemukan pada akunmu.']);
        }

        $customer->feedback()->create([
            'order_id' => $data['order_id'] ?? null,
            'type' => $data['type'],
            'rating' => $data['rating'] ?? null,
            'message' => $data['message'],
            'status' => Feedback::STATUS_NEW,
        ]);

        return redirect()->route('customer.feedback')->with('success', 'Masukanmu berhasil dikirim.');
    }
}
