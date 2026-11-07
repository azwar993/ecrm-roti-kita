@extends('layouts.app')
@section('title', $promotion->exists ? 'Edit Promosi' : 'Buat Promosi')
@section('breadcrumb', 'Promosi')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Customer retention</p><h1>{{ $promotion->exists ? 'Edit promosi' : 'Buat promosi' }}</h1><p>Harga dan diskon final dihitung oleh server saat checkout.</p></div><a class="rk-btn rk-btn-quiet" href="{{ route('admin.promotions') }}">Kembali</a></div>
<section class="rk-panel rk-panel-v2"><form method="POST" action="{{ $promotion->exists ? route('admin.promotions.update',$promotion) : route('admin.promotions.store') }}">@csrf @if($promotion->exists) @method('PUT') @endif
<div class="rk-real-form-grid">
<label class="rk-field"><span>Nama promosi</span><input name="name" value="{{ old('name',$promotion->name) }}" maxlength="150" required>@error('name')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Kode promosi</span><input name="code" value="{{ old('code',$promotion->code) }}" maxlength="50" required>@error('code')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Jenis diskon</span><select name="discount_type" required><option value="percentage" @selected(old('discount_type',$promotion->discount_type)==='percentage')>Persentase</option><option value="fixed" @selected(old('discount_type',$promotion->discount_type)==='fixed')>Nominal (Rp)</option></select>@error('discount_type')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Nilai diskon</span><input type="number" name="discount_value" min="0.01" step="0.01" value="{{ old('discount_value',$promotion->discount_value) }}" required>@error('discount_value')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Minimum pesanan (Rp)</span><input type="number" name="min_order_amount" min="0" step="0.01" value="{{ old('min_order_amount',$promotion->min_order_amount ?? 0) }}" required>@error('min_order_amount')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Maksimum diskon (Rp, opsional)</span><input type="number" name="max_discount_amount" min="0.01" step="0.01" value="{{ old('max_discount_amount',$promotion->max_discount_amount) }}">@error('max_discount_amount')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Mulai berlaku</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at',$promotion->starts_at?->format('Y-m-d\TH:i')) }}" required>@error('starts_at')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Berakhir</span><input type="datetime-local" name="ends_at" value="{{ old('ends_at',$promotion->ends_at?->format('Y-m-d\TH:i')) }}" required>@error('ends_at')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Batas penggunaan (opsional)</span><input type="number" name="usage_limit" min="1" step="1" value="{{ old('usage_limit',$promotion->usage_limit) }}">@error('usage_limit')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field rk-span-2"><span>Deskripsi (opsional)</span><textarea name="description" rows="3" maxlength="3000">{{ old('description',$promotion->description) }}</textarea>@error('description')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-check rk-span-2"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$promotion->exists ? $promotion->is_active : true))> Aktif dan dapat dipakai saat memenuhi periode dan syarat</label>
</div><div class="rk-real-form-footer"><a class="rk-btn rk-btn-quiet" href="{{ route('admin.promotions') }}">Batal</a><button class="rk-btn" type="submit">Simpan promosi</button></div></form></section>
@endsection
