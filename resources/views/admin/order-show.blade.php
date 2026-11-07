@extends('layouts.app')
@section('title', 'Kelola '.$order->order_number)
@section('breadcrumb', 'Detail pesanan')
@section('content')
@include('components.commerce-styles')
@php
    $statusLabels = [
        'pending' => 'Menunggu konfirmasi', 'confirmed' => 'Dikonfirmasi',
        'preparing' => 'Sedang disiapkan', 'ready' => 'Siap',
        'completed' => 'Selesai', 'cancelled' => 'Dibatalkan',
    ];
    $nextStatuses = match ($order->status) {
        'pending' => ['confirmed' => 'Konfirmasi', 'cancelled' => 'Batalkan'],
        'confirmed' => ['preparing' => 'Mulai disiapkan', 'cancelled' => 'Batalkan'],
        'preparing' => ['ready' => 'Tandai siap', 'cancelled' => 'Batalkan'],
        'ready' => ['completed' => 'Selesaikan', 'cancelled' => 'Batalkan'],
        default => [],
    };
    $paymentDetails = $order->payment?->payment_details ?? [];
@endphp
<div class="rk-commerce">
    <div class="rk-commerce-head">
        <div>
            <div class="rk-commerce-eyebrow">Admin · transaksi</div>
            <h1>{{ $order->order_number }}</h1>
            <p>{{ $order->customer_name_snapshot ?: $order->customer?->full_name ?: 'Pelanggan' }} · {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="rk-commerce-actions">
            <a class="rk-commerce-btn ghost" href="{{ route('admin.orders') }}">← Daftar pesanan</a>
            <a class="rk-commerce-btn" href="{{ route('admin.orders.receipt', $order) }}">Cetak struk</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="rk-commerce-errors" role="alert"><strong>Periksa data:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="rk-commerce-grid two">
        <section class="rk-commerce-panel">
            <h2 style="margin-top:0">Item pesanan</h2>
            <div class="rk-commerce-table-wrap">
                <table class="rk-commerce-table">
                    <thead><tr><th>Item</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    @forelse ($order->items as $item)
                        <tr>
                            <td><strong>{{ $item->item_name_snapshot }}</strong><small>{{ $item->item_sku_snapshot }}</small></td>
                            <td>{{ $item->quantity }}</td>
                            <td>Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format((float) $item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">Detail item tidak tersedia.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="rk-commerce-total"><span>Subtotal</span><span>Rp {{ number_format((float) $order->subtotal, 0, ',', '.') }}</span></div>
            <div class="rk-commerce-total"><span>Diskon</span><span>− Rp {{ number_format((float) $order->discount_amount, 0, ',', '.') }}</span></div>
            <div class="rk-commerce-total final"><span>Total</span><span>Rp {{ number_format((float) $order->total_amount, 0, ',', '.') }}</span></div>
            <hr class="rk-commerce-section-divider">
            <p>Jenis: <strong>{{ $order->order_type === 'dine_in' ? 'Dine-in' : 'Takeaway' }}</strong></p>
            @if ($order->table_number)<p>Meja: <strong>{{ $order->table_number }}</strong></p>@endif
            @if ($order->pickup_code)<p>Kode pickup: <strong>{{ $order->pickup_code }}</strong></p>@endif
            @if ($order->notes)<p>Catatan: {{ $order->notes }}</p>@endif
        </section>

        <div>
            <section class="rk-commerce-panel">
                <h2 style="margin-top:0">Status pesanan</h2>
                <p>Saat ini: <span class="rk-commerce-pill {{ $order->status === 'completed' ? 'green' : ($order->status === 'cancelled' ? 'red' : 'orange') }}">{{ $statusLabels[$order->status] ?? $order->status }}</span></p>
                @if ($nextStatuses)
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="rk-commerce-form-grid">
                        @csrf @method('PATCH')
                        <label class="rk-commerce-field full">Status berikutnya
                            <select class="rk-commerce-select" name="status" required>
                                @foreach ($nextStatuses as $status => $label)<option value="{{ $status }}">{{ $label }}</option>@endforeach
                            </select>
                        </label>
                        <label class="rk-commerce-field full">Catatan perubahan (opsional)<textarea class="rk-commerce-textarea" name="note" maxlength="1000"></textarea></label>
                        <div class="full"><button class="rk-commerce-btn" type="submit">Simpan status</button></div>
                    </form>
                @else
                    <p class="rk-commerce-note">Status ini merupakan status akhir atau tidak memiliki transisi yang tersedia.</p>
                @endif
            </section>

            <section class="rk-commerce-panel">
                <h2 style="margin-top:0">Verifikasi pembayaran</h2>
                @if ($order->payment)
                    <p>Metode: <strong>{{ ['cash' => 'Tunai di kasir', 'bank_transfer' => 'Transfer bank', 'qris' => 'QRIS'][$order->payment->method] ?? $order->payment->method }}</strong></p>
                    <p>Status: <span class="rk-commerce-pill {{ $order->payment->status === 'paid' ? 'green' : (in_array($order->payment->status, ['rejected', 'failed', 'refunded'], true) ? 'red' : 'orange') }}">{{ ucfirst(str_replace('_', ' ', $order->payment->status)) }}</span></p>
                    <p>Jumlah: <strong>Rp {{ number_format((float) $order->payment->amount, 0, ',', '.') }}</strong></p>
                    @if ($order->payment->paid_at)<p>Dibayar pada: {{ $order->payment->paid_at->format('d/m/Y H:i') }}</p>@endif
                    @if ($order->payment->verifiedBy)<p>Diverifikasi oleh: {{ $order->payment->verifiedBy->name }}</p>@endif

                    @if ($order->payment->method === 'cash')
                        <div class="rk-commerce-note">Pembayaran tunai dicatat setelah uang diterima kasir.</div>
                    @elseif ($order->payment->method === 'qris')
                        <div class="rk-commerce-note"><strong>Instruksi QRIS dari snapshot pesanan</strong>
                            @if (!empty($paymentDetails['qris_image']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($paymentDetails['qris_image']))
                                <img src="{{ asset('storage/'.ltrim($paymentDetails['qris_image'], '/')) }}" alt="QRIS merchant untuk pesanan {{ $order->order_number }}" style="display:block;max-width:280px;width:100%;height:auto;margin:12px auto">
                            @else
                                <p>Snapshot QRIS tidak tersedia untuk pesanan ini.</p>
                            @endif
                            <p>{{ $paymentDetails['instruction'] ?? 'Scan QRIS dan bayar sesuai total tagihan.' }}</p>
                        </div>
                    @elseif ($order->payment->method === 'bank_transfer')
                        <div class="rk-commerce-note"><strong>Rekening dari snapshot pesanan</strong>
                            @if (!empty($paymentDetails['bank_name']) && !empty($paymentDetails['bank_account_name']) && !empty($paymentDetails['bank_account_number']))
                                <p>{{ $paymentDetails['bank_name'] }} · {{ $paymentDetails['bank_account_number'] }} a.n. {{ $paymentDetails['bank_account_name'] }}</p>
                            @else
                                <p>Snapshot rekening tidak tersedia pada pesanan lama ini.</p>
                            @endif
                            <p>{{ $paymentDetails['instruction'] ?? 'Transfer sesuai total tagihan dan unggah bukti pembayaran.' }}</p>
                        </div>
                    @endif

                    @if ($order->payment->proof_path)
                        <p><a href="{{ route('admin.orders.payment-proof', $order) }}" target="_blank" rel="noopener">Lihat bukti pembayaran</a></p>
                        @if (!str_ends_with(strtolower($order->payment->proof_path), '.pdf'))
                            <img src="{{ route('admin.orders.payment-proof', $order) }}" alt="Bukti pembayaran {{ $order->order_number }}" style="display:block;max-width:100%;max-height:520px;border-radius:12px;margin:12px 0">
                        @endif
                    @endif
                    @if ($order->payment->rejection_reason)<div class="rk-commerce-errors">Alasan penolakan: {{ $order->payment->rejection_reason }}</div>@endif

                    @if ($order->payment->status === 'awaiting_verification' || ($order->payment->method === 'cash' && $order->payment->status === 'pending') || $order->payment->status === 'paid')
                        <form method="POST" action="{{ route('admin.orders.payment', $order) }}" class="rk-commerce-form-grid">
                            @csrf @method('PATCH')
                            <label class="rk-commerce-field full">Aksi pembayaran
                                <select class="rk-commerce-select" name="status" required>
                                    @if ($order->payment->status === 'paid')
                                        <option value="refunded">Catat refund</option>
                                    @else
                                        <option value="paid">Konfirmasi pembayaran diterima</option>
                                        @if ($order->payment->status === 'awaiting_verification')<option value="rejected">Tolak bukti</option>@endif
                                    @endif
                                </select>
                            </label>
                            <label class="rk-commerce-field full">Catatan / alasan (opsional)<textarea class="rk-commerce-textarea" name="notes" maxlength="1000"></textarea></label>
                            <div class="full"><button class="rk-commerce-btn" type="submit">Simpan verifikasi</button></div>
                        </form>
                    @else
                        <p class="rk-commerce-note">Pembayaran berstatus {{ $order->payment->status }}.</p>
                    @endif
                @else
                    <p>Data pembayaran tidak ditemukan.</p>
                @endif
            </section>
        </div>
    </div>

    <section class="rk-commerce-panel">
        <h2 style="margin-top:0">Riwayat status</h2>
        @forelse ($order->statusHistories as $history)
            <div class="rk-commerce-total"><div><strong>{{ $statusLabels[$history->status] ?? ucfirst($history->status) }}</strong>@if ($history->note)<small>{{ $history->note }}</small>@endif</div><small>{{ $history->created_at?->format('d/m/Y H:i') }}</small></div>
        @empty
            <p>Belum ada riwayat status.</p>
        @endforelse
    </section>
</div>
@endsection
