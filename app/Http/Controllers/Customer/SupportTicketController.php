<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Notifications\SupportTicketUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        return view('customer.tickets.index', ['tickets' => SupportTicket::query()->where('user_id', $request->user()->id)->latest()->paginate(12)]);
    }

    public function create(Request $request): View
    {
        $customerId = Customer::query()->where('user_id', $request->user()->id)->value('id');

        return view('customer.tickets.create', ['orders' => $customerId ? Order::query()->where('customer_id', $customerId)->latest()->limit(50)->get() : collect()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $customerId = Customer::query()->where('user_id', $request->user()->id)->value('id');
        $data = $request->validate([
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'subject' => ['required', 'string', 'max:180'],
            'message' => ['required', 'string', 'max:10000'],
            'order_id' => ['nullable', Rule::exists('orders', 'id')->where('customer_id', $customerId ?? 0)],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $ticket = DB::transaction(function () use ($request, $data): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'ticket_number' => 'RK-'.Str::upper((string) Str::uuid()),
                'user_id' => $request->user()->id,
                'order_id' => $data['order_id'] ?? null,
                'category' => $data['category'],
                'subject' => $data['subject'],
                'status' => 'open',
                'priority' => 'normal',
            ]);
            $message = $ticket->messages()->create(['user_id' => $request->user()->id, 'message' => $data['message']]);
            $this->storeAttachment($request, $message);

            return $ticket;
        });

        Notification::send(User::query()->where('role', 'admin')->where('is_active', true)->get(), new SupportTicketUpdated($ticket, 'created'));

        return redirect()->route('customer.tickets.show', $ticket)->with('success', 'Tiket bantuan berhasil dibuat.');
    }

    public function show(Request $request, SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);
        $ticket->load(['messages.user', 'messages.attachments']);

        return view('customer.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_unless($ticket->user_id === $request->user()->id, 404);
        abort_if($ticket->status === 'closed', 409, 'Tiket telah ditutup.');
        $data = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        DB::transaction(function () use ($request, $ticket, $data): void {
            $message = $ticket->messages()->create(['user_id' => $request->user()->id, 'message' => $data['message']]);
            $this->storeAttachment($request, $message);
            if ($ticket->status === 'waiting_customer') {
                $ticket->update(['status' => 'in_progress']);
            }
        });
        Notification::send(User::query()->where('role', 'admin')->where('is_active', true)->get(), new SupportTicketUpdated($ticket, 'reply'));

        return back()->with('success', 'Balasan berhasil dikirim.');
    }

    public function download(Request $request, TicketAttachment $attachment)
    {
        $attachment->load('message.ticket');
        abort_unless($request->user()->role === 'admin' || $attachment->message->ticket->user_id === $request->user()->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function storeAttachment(Request $request, $message): void
    {
        if (! $request->hasFile('attachment')) {
            return;
        }

        $file = $request->file('attachment');
        $path = $file->store('support-tickets', 'local');
        $message->attachments()->create([
            'disk' => 'local', 'path' => $path, 'original_name' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getMimeType(), 'size' => $file->getSize(),
        ]);
    }
}
