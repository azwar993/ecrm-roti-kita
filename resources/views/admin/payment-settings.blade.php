@extends('layouts.app')
@section('title', 'Pengaturan Pembayaran')
@section('breadcrumb', 'Pengaturan pembayaran')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Merchant RotiKita</p><h1>Pengaturan pembayaran</h1><p>Atur QRIS resmi dan rekening transfer yang ditampilkan kepada customer.</p></div></div>
<section class="rk-panel rk-panel-v2">@if($errors->any())<div class="rk-commerce-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<form method="POST" enctype="multipart/form-data" action="{{ route('admin.payment-settings.update') }}" class="rk-form-grid">@csrf @method('PUT')
<label class="rk-field"><span>Gambar QRIS resmi</span><input type="file" name="qris_image" id="qris-image-input" accept="image/jpeg,image/png,image/webp">@if($qrisImageAvailable)<img src="{{ $qrisImageUrl }}" alt="QRIS RotiKita saat ini" style="display:block;width:min(100%,280px);margin-top:12px;border:1px solid #ddd;padding:8px;border-radius:12px"><small>{{ $settings->qris_image }}</small>@elseif($settings?->qris_image)<small>File konfigurasi tercatat tetapi gambar tidak ditemukan pada storage publik.</small>@endif<img id="qris-image-preview" alt="Preview QRIS baru" hidden style="max-width:280px;width:100%;margin-top:12px;border-radius:12px"><small>Gambar QRIS lama dipertahankan untuk detail pesanan historis.</small></label>
<label class="rk-field"><span><input type="checkbox" name="remove_qris" value="1"> Hapus konfigurasi QRIS</span><small>QRIS akan dinonaktifkan sampai gambar resmi diunggah kembali.</small></label>
<label class="rk-field"><span>Nama bank</span><input type="text" name="bank_name" maxlength="100" value="{{ old('bank_name',$settings?->bank_name) }}"></label>
<label class="rk-field"><span>Nama pemilik rekening</span><input type="text" name="bank_account_name" maxlength="150" value="{{ old('bank_account_name',$settings?->bank_account_name) }}"></label>
<label class="rk-field"><span>Nomor rekening</span><input type="text" name="bank_account_number" maxlength="80" value="{{ old('bank_account_number',$settings?->bank_account_number) }}"></label>
<button class="rk-btn" type="submit">Simpan konfigurasi</button></form></section>
<script>document.addEventListener('DOMContentLoaded',()=>{const input=document.getElementById('qris-image-input');const preview=document.getElementById('qris-image-preview');if(!input||!preview)return;input.addEventListener('change',()=>{const file=input.files?.[0];if(!file){preview.hidden=true;preview.removeAttribute('src');return;}if(!['image/jpeg','image/png','image/webp'].includes(file.type)){preview.hidden=true;return;}preview.src=URL.createObjectURL(file);preview.hidden=false;});});</script>
@endsection
