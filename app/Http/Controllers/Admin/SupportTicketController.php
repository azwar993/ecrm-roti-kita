<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from']]);
        $query = SupportTicket::query()->with('user')->latest();
        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }
        foreach (['status' => SupportTicket::STATUSES, 'category' => SupportTicket::CATEGORIES, 'priority' => SupportTicket::PRIORITIES] as $field => $values) {
            if (in_array($request->query($field), $values, true)) {
                $query->where($field, $request->query($field));
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        return view('admin.tickets.index', ['tickets' => $query->paginate(15)->withQueryString(), 'search' => $search]);
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'order', 'messages.user', 'messages.attachments']);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket): RedirectResponse
    {
        abort_if($ticket->status === 'closed', 409, 'Tiket telah ditutup.');
        $data = $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        DB::transaction(function () use ($request, $ticket, $data): void {
            $message = $ticket->messages()->create(['user_id' => $request->user()->id, 'message' => $data['message']]);
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('support-tickets', 'local');
                $message->attachments()->create(['disk' => 'local', 'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime_type' => (string) $file->getMimeType(), 'size' => $file->getSize()]);
            }
            $ticket->update(['status' => 'waiting_customer']);
        });
        $ticket->user->notify(new SupportTicketUpdated($ticket, 'reply'));

        return back()->with('success', 'Balasan berhasil dikirim.');
    }

    public function update(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(SupportTicket::STATUSES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
        ]);
        $transitions = [
            'open' => ['in_progress', 'waiting_customer', 'resolved', 'closed'],
            'in_progress' => ['waiting_customer', 'resolved', 'closed'],
            'waiting_customer' => ['in_progress', 'resolved', 'closed'],
            'resolved' => ['in_progress', 'closed'],
            'closed' => [],
        ];
        abort_unless($data['status'] === $ticket->status || in_array($data['status'], $transitions[$ticket->status] ?? [], true), 422);
        $statusChanged = $data['status'] !== $ticket->status;
        DB::transaction(function () use ($ticket, $data): void {
            $ticket->update([
                ...$data,
                'resolved_at' => $data['status'] === 'resolved' ? ($ticket->resolved_at ?? now()) : null,
                'closed_at' => $data['status'] === 'closed' ? ($ticket->closed_at ?? now()) : null,
            ]);
        });
        if ($statusChanged) {
            $ticket->user->notify(new SupportTicketUpdated($ticket, 'status'));
        }

        return back()->with('success', 'Status tiket berhasil diperbarui.');
    }
}
