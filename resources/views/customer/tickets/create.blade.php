@extends('layouts.app')
@section('title', 'Buat Tiket Bantuan')
@section('breadcrumb', 'Pusat Bantuan')
@section('content')
<div class="rk-page-heading"><div><p class="rk-eyebrow">Kami siap membantu</p><h1>Buat tiket bantuan</h1></div></div>
<section class="rk-panel rk-panel-v2">@if($errors->any())<div role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<form method="POST" enctype="multipart/form-data" action="{{ route('customer.tickets.store') }}">@csrf<div class="rk-real-form-grid">
<label class="rk-field"><span>Kategori</span><select name="category" required>@foreach(['order'=>'Pesanan','payment'=>'Pembayaran','product'=>'Produk','delivery'=>'Pengiriman/Pengambilan','promotion'=>'Promo','account'=>'Akun','other'=>'Lainnya'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
<label class="rk-field"><span>Pesanan terkait (opsional)</span><select name="order_id"><option value="">Pilih pesanan</option>@foreach($orders as $order)<option value="{{ $order->id }}">{{ $order->order_number }}</option>@endforeach</select></label>
<label class="rk-field rk-span-2"><span>Subjek</span><input name="subject" maxlength="180" required value="{{ old('subject') }}"></label><label class="rk-field rk-span-2"><span>Pesan</span><textarea name="message" rows="6" maxlength="10000" required>{{ old('message') }}</textarea></label><label class="rk-field rk-span-2"><span>Lampiran gambar atau PDF (maks. 5 MB)</span><input type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf"></label>
</div><button class="rk-btn" type="submit">Kirim tiket</button></form></section>
@endsection
