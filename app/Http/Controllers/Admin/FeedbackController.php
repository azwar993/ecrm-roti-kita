<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $query = Feedback::query()->with(['customer', 'order'])->latest('id');
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('message', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($customer) => $customer->where('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('order', fn ($order) => $order->where('order_number', 'like', "%{$search}%"));
            });
        }
        if ($status !== 'all' && in_array($status, [Feedback::STATUS_NEW, Feedback::STATUS_IN_PROGRESS, Feedback::STATUS_RESOLVED, Feedback::STATUS_CLOSED], true)) {
            $query->where('status', $status);
        }

        return view('admin.feedback', [
            'feedbacks' => $query->paginate(12)->withQueryString(),
            'search' => $search,
            'status' => $status,
            'newCount' => Feedback::query()->where('status', Feedback::STATUS_NEW)->count(),
            'inProgressCount' => Feedback::query()->where('status', Feedback::STATUS_IN_PROGRESS)->count(),
            'resolvedCount' => Feedback::query()->where('status', Feedback::STATUS_RESOLVED)->count(),
        ]);
    }

    public function update(Request $request, Feedback $feedback): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Feedback::STATUS_NEW, Feedback::STATUS_IN_PROGRESS, Feedback::STATUS_RESOLVED, Feedback::STATUS_CLOSED])],
            'response' => ['nullable', 'string', 'max:5000'],
        ]);
        $feedback->status = $data['status'];
        $feedback->response = trim((string) ($data['response'] ?? '')) ?: null;
        $feedback->handled_by = $request->user()->id;
        $feedback->responded_at = $feedback->response ? now() : null;
        $feedback->save();

        return back()->with('success', 'Tindak lanjut feedback berhasil disimpan.');
    }
}
