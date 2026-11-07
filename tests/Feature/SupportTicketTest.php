<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\SupportTicketUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_ticket_and_cannot_access_another_customers_ticket(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $otherCustomer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($customer)->post(route('customer.tickets.store'), [
            'category' => 'payment', 'subject' => 'Pertanyaan pembayaran', 'message' => 'Mohon bantu cek transaksi.',
        ])->assertRedirect();

        $ticket = SupportTicket::query()->firstOrFail();
        $this->assertSame($customer->id, $ticket->user_id);
        $this->assertSame('open', $ticket->status);
        $this->assertSame('normal', $ticket->priority);
        $this->actingAs($customer)->get(route('customer.tickets.show', $ticket))->assertOk()->assertSee($ticket->ticket_number);
        Notification::assertSentTo($admin, SupportTicketUpdated::class);
        $this->actingAs($otherCustomer)->get(route('customer.tickets.show', $ticket))->assertNotFound();
        $this->actingAs($otherCustomer)->post(route('customer.tickets.reply', $ticket), ['message' => 'Tidak sah'])->assertNotFound();
    }

    public function test_private_ticket_attachment_is_authorized_and_admin_can_reply_and_update_status(): void
    {
        Notification::fake();
        Storage::fake('local');
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $otherCustomer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $ticket = SupportTicket::query()->create([
            'ticket_number' => 'RK-TEST-'.uniqid(), 'user_id' => $customer->id,
            'category' => 'product', 'subject' => 'Produk', 'status' => 'open', 'priority' => 'normal',
        ]);
        $message = $ticket->messages()->create(['user_id' => $customer->id, 'message' => 'Foto terlampir.']);
        $path = UploadedFile::fake()->image('roti.jpg')->store('support-tickets', 'local');
        $attachment = $message->attachments()->create([
            'disk' => 'local', 'path' => $path, 'original_name' => 'roti.jpg', 'mime_type' => 'image/jpeg', 'size' => 100,
        ]);

        $this->actingAs($otherCustomer)->get(route('customer.tickets.attachments.download', $attachment))->assertNotFound();
        $this->actingAs($customer)->get(route('customer.tickets.attachments.download', $attachment))->assertOk();
        $this->actingAs($admin)->get(route('admin.tickets.index'))->assertOk()->assertSee($ticket->ticket_number);
        $this->actingAs($admin)->get(route('admin.tickets.show', $ticket))->assertOk()->assertSee($ticket->subject);
        $this->actingAs($admin)->post(route('admin.tickets.reply', $ticket), ['message' => 'Kami sedang memeriksa.'])->assertRedirect();
        $this->assertDatabaseHas('support_tickets', ['id' => $ticket->id, 'status' => 'waiting_customer']);
        Notification::assertSentTo($customer, SupportTicketUpdated::class);
        $this->actingAs($admin)->patch(route('admin.tickets.update', $ticket), ['status' => 'resolved', 'priority' => 'high'])->assertRedirect();
        $this->assertDatabaseHas('support_tickets', ['id' => $ticket->id, 'status' => 'resolved', 'priority' => 'high']);
    }

    public function test_invalid_ticket_category_and_attachment_are_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'is_active' => true]);
        $this->actingAs($customer)->from(route('customer.tickets.create'))->post(route('customer.tickets.store'), [
            'category' => 'unknown', 'subject' => 'Test', 'message' => 'Isi',
            'attachment' => UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors(['category', 'attachment']);
    }
}
