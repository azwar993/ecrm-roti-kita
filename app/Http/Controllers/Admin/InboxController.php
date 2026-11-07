<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Notifications\OrderCreated;
use App\Notifications\PaymentProofSubmitted;
use App\Notifications\PaymentStatusChanged;
use App\Notifications\SupportTicketUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InboxController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $types = [PaymentProofSubmitted::class, OrderCreated::class, PaymentStatusChanged::class, SupportTicketUpdated::class];
        $query = $request->user()->notifications()->whereIn('type', $types)->latest();
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        }
        if ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        return view('admin.inbox', ['notifications' => $query->paginate(15)->withQueryString(), 'filter' => $filter]);
    }

    public function open(Request $request, string $notification): RedirectResponse
    {
        $item = $request->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $orderId = (int) ($item->data['order_id'] ?? 0);

        if (isset($item->data['ticket_id'])) {
            return redirect()->route('admin.tickets.show', $item->data['ticket_id']);
        }

        return redirect()->route('admin.orders.show', $orderId);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->whereIn('type', [
            PaymentProofSubmitted::class,
            OrderCreated::class,
            PaymentStatusChanged::class,
            SupportTicketUpdated::class,
        ])->update(['read_at' => now()]);

        return back()->with('success', 'Inbox ditandai telah dibaca.');
    }

    public function proof(Request $request, int $order): BinaryFileResponse
    {
        $payment = Payment::query()->where('order_id', $order)->firstOrFail();
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return response()->file(Storage::disk('local')->path($payment->proof_path));
    }
}
